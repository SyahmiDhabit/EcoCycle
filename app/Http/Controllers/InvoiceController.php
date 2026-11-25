<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\MaterialExchange;
use App\Models\Payment;
use App\Models\User;
use App\Services\InvoicePdfGenerator;
use App\Services\StripePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly StripePaymentService $stripeService,
    ) {}

    private function checkApproved(): void
    {
        abort_unless(Auth::user()->account_status === 'approved', 403, 'Your business application must be approved first.');
    }

    /**
     * Business Dashboard Billing Hub: My Orders, Payment History, and My Invoices.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        // Automatically sync any pending Stripe checkout sessions for this business
        $this->stripeService->syncPendingStripePaymentsForBusiness($business->id);

        $tab = $request->input('tab', 'invoices');
        if (! in_array($tab, ['orders', 'payments', 'invoices'], true)) {
            $tab = 'invoices';
        }

        $orders = MaterialExchange::with(['item', 'provider', 'requester', 'invoice', 'latestPayment'])
            ->where(function ($q) use ($business): void {
                $q->where('provider_business_id', $business->id)
                    ->orWhere('requester_business_id', $business->id);
            })
            ->latest()
            ->paginate(15, ['*'], 'orders_page')
            ->withQueryString();

        $payments = Payment::with(['exchange.item', 'buyer', 'seller', 'invoice'])
            ->where(function ($q) use ($business): void {
                $q->where('buyer_business_id', $business->id)
                    ->orWhere('seller_business_id', $business->id);
            })
            ->latest()
            ->paginate(15, ['*'], 'payments_page')
            ->withQueryString();

        $invoices = Invoice::with(['seller', 'buyer', 'payment', 'exchange'])
            ->where(function ($q) use ($business): void {
                $q->where('seller_business_id', $business->id)
                    ->orWhere('buyer_business_id', $business->id);
            })
            ->latest('issued_at')
            ->paginate(15, ['*'], 'invoices_page')
            ->withQueryString();

        $totalInvoicesCount = Invoice::where('seller_business_id', $business->id)
            ->orWhere('buyer_business_id', $business->id)
            ->count();

        $totalPaidOutRm = (float) Payment::where('buyer_business_id', $business->id)
            ->where('status', 'paid')
            ->sum('amount');

        $totalReceivedRm = (float) Payment::where('seller_business_id', $business->id)
            ->where('status', 'paid')
            ->sum('amount');

        $unpaidOrdersCount = MaterialExchange::where('requester_business_id', $business->id)
            ->where('listing_type', 'buy')
            ->where('total_price', '>', 0)
            ->where('payment_status', '!=', 'paid')
            ->count();

        return view('business.invoices.index', [
            'business' => $business,
            'activeTab' => $tab,
            'orders' => $orders,
            'payments' => $payments,
            'invoices' => $invoices,
            'totalInvoicesCount' => $totalInvoicesCount,
            'totalPaidOutRm' => $totalPaidOutRm,
            'totalReceivedRm' => $totalReceivedRm,
            'unpaidOrdersCount' => $unpaidOrdersCount,
        ]);
    }

    /**
     * View a single Malaysian business invoice (strictly buyer or seller only).
     */
    public function show(Invoice $invoice): View
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        abort_unless($invoice->isAccessibleBy($business->id), 403, 'You are not authorized to view this invoice.');

        $invoice->load(['seller', 'buyer', 'payment', 'exchange']);

        return view('business.invoices.show', [
            'business' => $business,
            'invoice' => $invoice,
        ]);
    }

    /**
     * Download the invoice as a cleanly formatted PDF (strictly buyer or seller only).
     */
    public function downloadPdf(Request $request, Invoice $invoice, InvoicePdfGenerator $pdfGenerator): Response
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        abort_unless($invoice->isAccessibleBy($business->id), 403, 'You are not authorized to download this invoice.');

        $pdfBytes = $pdfGenerator->generate($invoice);
        $safeFilename = preg_replace('/[^A-Za-z0-9\-_]/', '', (string) $invoice->invoice_number) ?: 'ecocycle-invoice';

        $disposition = $request->boolean('inline') ? 'inline' : 'attachment';

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$safeFilename.'.pdf"',
            'Content-Length' => (string) strlen($pdfBytes),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}

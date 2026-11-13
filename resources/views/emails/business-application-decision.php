<?php

use App\Models\User;

/** @var User $business */
/** @var string $decision */
$isApproved = $decision === 'approved';
$isRejected = $decision === 'rejected';
$isChangesRequested = ! $isApproved && ! $isRejected;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoCycle Business Application Status</title>
</head>
<body style="margin:0;padding:24px 12px;background:#f6f7f3;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#22352c;line-height:1.6;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;margin:0 auto;background:#ffffff;border:1px solid #dde4d9;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.04);">
        <!-- Header Brand Bar -->
        <tr>
            <td style="padding:24px 32px;background:#285c44;color:#ffffff;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <div style="font-size:20px;font-weight:800;letter-spacing:-0.03em;color:#ffffff;">EcoCycle</div>
                            <div style="font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#d5e7db;margin-top:2px;">Business Verification Decision</div>
                        </td>
                        <td align="right">
                            <?php if ($isApproved) { ?>
                                <span style="display:inline-block;padding:4px 12px;border-radius:99px;background:#e6f0e6;color:#1e5e3a;font-size:12px;font-weight:700;">Approved</span>
                            <?php } elseif ($isRejected) { ?>
                                <span style="display:inline-block;padding:4px 12px;border-radius:99px;background:#fde8e4;color:#a0483e;font-size:12px;font-weight:700;">Rejected</span>
                            <?php } else { ?>
                                <span style="display:inline-block;padding:4px 12px;border-radius:99px;background:#fef3c7;color:#92400e;font-size:12px;font-weight:700;">Changes Required</span>
                            <?php } ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Main Body Content -->
        <tr>
            <td style="padding:32px;">
                <?php if ($isApproved) { ?>
                    <!-- APPROVED SCENARIO -->
                    <h1 style="margin:0 0 12px;font-size:22px;color:#183624;font-weight:700;">Your business application has been approved!</h1>
                    <p style="margin:0 0 16px;font-size:14px;color:#3f5146;">
                        Hello <strong><?= e($business->name) ?></strong>, congratulations! Your business registration for <strong><?= e($business->business_name) ?></strong> has been verified and approved by the EcoCycle administrator team.
                    </p>

                    <!-- Application Summary Box -->
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;background:#f8faf7;border:1px solid #e1e9df;border-radius:8px;padding:16px;">
                        <tr>
                            <td style="padding:6px 0;font-size:12px;color:#6b7b71;width:140px;">Registered Business:</td>
                            <td style="padding:6px 0;font-size:13px;font-weight:600;color:#183624;"><?= e($business->business_name) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;font-size:12px;color:#6b7b71;">SSM Number:</td>
                            <td style="padding:6px 0;font-size:13px;font-weight:600;color:#183624;"><?= e($business->business_registration_number) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;font-size:12px;color:#6b7b71;">Application Status:</td>
                            <td style="padding:6px 0;font-size:13px;font-weight:700;color:#1e5e3a;">Verified &amp; Active</td>
                        </tr>
                    </table>

                    <p style="margin:0 0 20px;font-size:14px;color:#3f5146;">
                        You now have unrestricted access to the complete EcoCycle workspace:
                    </p>
                    <ul style="margin:0 0 24px;padding-left:20px;font-size:13px;color:#3f5146;">
                        <li style="margin-bottom:6px;"><strong>Surplus Materials Marketplace:</strong> Browse, list, and trade recyclable resources.</li>
                        <li style="margin-bottom:6px;"><strong>Internal Inventory Stock:</strong> Track warehouse supplies, batch weights, and scrap materials.</li>
                        <li style="margin-bottom:6px;"><strong>Verified SME Directory:</strong> Discover verified Malaysian businesses and initiate exchanges.</li>
                    </ul>

                    <div style="text-align:center;margin:28px 0 12px;">
                        <a href="<?= e(route('login')) ?>" style="display:inline-block;padding:12px 28px;background:#285c44;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;border-radius:7px;box-shadow:0 2px 6px rgba(40,92,68,0.25);">
                            Sign in to Business Dashboard &rarr;
                        </a>
                    </div>

                <?php } elseif ($isRejected) { ?>
                    <!-- REJECTED SCENARIO -->
                    <h1 style="margin:0 0 12px;font-size:22px;color:#a0483e;font-weight:700;">Business Registration Status: Application Rejected</h1>
                    <p style="margin:0 0 16px;font-size:14px;color:#3f5146;">
                        Hello <strong><?= e($business->name) ?></strong>,
                    </p>
                    <p style="margin:0 0 16px;font-size:14px;color:#3f5146;">
                        We regret to inform you that your registration application for <strong><?= e($business->business_name) ?></strong> on EcoCycle has been reviewed and <strong style="color:#a0483e;">rejected</strong> by an administrator.
                    </p>

                    <!-- Application Summary Box -->
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;background:#fbfbfb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
                        <tr>
                            <td style="padding:6px 0;font-size:12px;color:#6b7b71;width:140px;">Registered Business:</td>
                            <td style="padding:6px 0;font-size:13px;font-weight:600;color:#183624;"><?= e($business->business_name) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;font-size:12px;color:#6b7b71;">SSM Number:</td>
                            <td style="padding:6px 0;font-size:13px;font-weight:600;color:#183624;"><?= e($business->business_registration_number) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;font-size:12px;color:#6b7b71;">Application Status:</td>
                            <td style="padding:6px 0;font-size:13px;font-weight:700;color:#a0483e;">Registration Rejected</td>
                        </tr>
                    </table>

                    <!-- Admin Remarks / Rejection Reason Box -->
                    <div style="background:#fef4f2;border-left:4px solid #a0483e;border-top:1px solid #fbd6d0;border-right:1px solid #fbd6d0;border-bottom:1px solid #fbd6d0;border-radius:6px;padding:16px;margin:22px 0;">
                        <strong style="display:block;font-size:13px;color:#a0483e;margin-bottom:6px;">Administrator Remarks &amp; Reason for Rejection:</strong>
                        <div style="font-size:13px;color:#22352c;line-height:1.6;background:#ffffff;padding:12px 14px;border-radius:6px;border:1px solid #f5cfc7;">
                            <?= nl2br(e($business->review_note ?: 'The submitted credentials or business registration documents did not meet our verification criteria.')) ?>
                        </div>
                    </div>

                    <h3 style="margin:22px 0 8px;font-size:14px;color:#183624;">Next Steps &amp; Instructions:</h3>
                    <p style="margin:0 0 14px;font-size:13px;color:#3f5146;">
                        Because this application has been rejected, platform marketplace and business features cannot be unlocked for this record.
                    </p>
                    <ul style="margin:0 0 24px;padding-left:20px;font-size:13px;color:#3f5146;">
                        <li style="margin-bottom:6px;">If you have valid and updated SSM company credentials, you may submit a fresh business application with clear documents.</li>
                        <li style="margin-bottom:6px;">If you believe this decision was made in error, you may reply to this email or contact support at <strong>ssyahmidhabit@gmail.com</strong>.</li>
                    </ul>

                    <div style="text-align:center;margin:28px 0 12px;">
                        <a href="<?= e(route('home')) ?>" style="display:inline-block;padding:12px 28px;background:#285c44;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;border-radius:7px;">
                            Visit EcoCycle Platform &rarr;
                        </a>
                    </div>

                <?php } else { ?>
                    <!-- REQUEST CHANGES SCENARIO -->
                    <h1 style="margin:0 0 12px;font-size:22px;color:#92400e;font-weight:700;">Action Required: Changes Requested for Your Business Application</h1>
                    <p style="margin:0 0 16px;font-size:14px;color:#3f5146;">
                        Hello <strong><?= e($business->name) ?></strong>,
                    </p>
                    <p style="margin:0 0 16px;font-size:14px;color:#3f5146;">
                        An administrator has reviewed your business registration for <strong><?= e($business->business_name) ?></strong> and requested updates before your account can be approved.
                    </p>

                    <!-- Application Summary Box -->
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;background:#fefbf4;border:1px solid #fde68a;border-radius:8px;padding:16px;">
                        <tr>
                            <td style="padding:6px 0;font-size:12px;color:#6b7b71;width:140px;">Registered Business:</td>
                            <td style="padding:6px 0;font-size:13px;font-weight:600;color:#183624;"><?= e($business->business_name) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;font-size:12px;color:#6b7b71;">SSM Number:</td>
                            <td style="padding:6px 0;font-size:13px;font-weight:600;color:#183624;"><?= e($business->business_registration_number) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;font-size:12px;color:#6b7b71;">Application Status:</td>
                            <td style="padding:6px 0;font-size:13px;font-weight:700;color:#92400e;">Changes Required</td>
                        </tr>
                    </table>

                    <!-- Admin Remarks Box -->
                    <div style="background:#fffbeb;border-left:4px solid #b45309;border-top:1px solid #fef3c7;border-right:1px solid #fef3c7;border-bottom:1px solid #fef3c7;border-radius:6px;padding:16px;margin:22px 0;">
                        <strong style="display:block;font-size:13px;color:#92400e;margin-bottom:6px;">Administrator Remarks &mdash; What Needs to be Fixed:</strong>
                        <div style="font-size:13px;color:#22352c;line-height:1.6;background:#ffffff;padding:12px 14px;border-radius:6px;border:1px solid #fde68a;">
                            <?= nl2br(e($business->review_note ?: 'Please review your registration details or replace your uploaded SSM documents.')) ?>
                        </div>
                    </div>

                    <h3 style="margin:22px 0 8px;font-size:14px;color:#183624;">Instructions for Next Steps:</h3>
                    <ol style="margin:0 0 24px;padding-left:20px;font-size:13px;color:#3f5146;">
                        <li style="margin-bottom:8px;">Sign in to your EcoCycle account using your registered credentials.</li>
                        <li style="margin-bottom:8px;">On your pending approval screen, review the reviewer's feedback and click <strong>"Edit application"</strong>.</li>
                        <li style="margin-bottom:8px;">Correct any necessary information or upload replacement SSM certificates/profiles.</li>
                        <li style="margin-bottom:8px;">Click <strong>"Save &amp; Resubmit Changes"</strong> to submit your updated application for priority re-review.</li>
                    </ol>

                    <div style="text-align:center;margin:28px 0 12px;">
                        <a href="<?= e(route('login')) ?>" style="display:inline-block;padding:12px 28px;background:#285c44;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;border-radius:7px;box-shadow:0 2px 6px rgba(40,92,68,0.25);">
                            Sign in &amp; Update Application &rarr;
                        </a>
                    </div>
                <?php } ?>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding:20px 32px;background:#f9faf8;border-top:1px solid #dde4d9;font-size:12px;color:#718277;text-align:center;">
                <p style="margin:0 0 4px;"><strong>EcoCycle</strong> &middot; Malaysia Circular Economy &amp; Industrial Waste Trading Platform</p>
                <p style="margin:0;">This notification was sent to <a href="mailto:<?= e($business->email) ?>" style="color:#285c44;text-decoration:none;"><?= e($business->email) ?></a>.</p>
            </td>
        </tr>
    </table>
</body>
</html>

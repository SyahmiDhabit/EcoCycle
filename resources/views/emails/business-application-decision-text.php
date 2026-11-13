<?php

use App\Models\User;

/** @var User $business */
/** @var string $decision */
?>
EcoCycle - Platform Business Verification Decision
===================================================

Business: <?= $business->business_name ?> (<?= $business->business_registration_number ?>)
Status: <?= match ($decision) {
    'approved' => 'Verified & Approved',
    'rejected' => 'Registration Rejected',
    default => 'Changes Requested',
} ?>

<?php if ($decision === 'approved') { ?>
Hello <?= $business->name ?>,

Congratulations! Your business registration for <?= $business->business_name ?> has been verified and approved by the EcoCycle administrator team.
You now have full verified access to the circular marketplace, internal inventory, and circular business directory.

Sign in to your dashboard: <?= route('login') ?>
<?php } elseif ($decision === 'rejected') { ?>
Hello <?= $business->name ?>,

We regret to inform you that your registration application for <?= $business->business_name ?> has been reviewed and rejected by an administrator.

Administrator Remarks / Reason:
<?= $business->review_note ?: 'The submitted credentials did not meet our verification criteria.' ?>

Visit EcoCycle: <?= route('home') ?>
<?php } else { ?>
Hello <?= $business->name ?>,

An administrator has reviewed your business registration for <?= $business->business_name ?> and requested updates before your account can be approved.

Administrator Remarks:
<?= $business->review_note ?: 'Please review your registration details or replace your uploaded SSM documents.' ?>

Next Steps:
1. Sign in to your account: <?= route('login') ?>
2. On your pending approval screen, review the remarks and click "Edit application".
3. Save and resubmit changes for priority re-review.
<?php } ?>

---------------------------------------------------
EcoCycle Malaysia - Circular Economy Platform
This notification was sent to <?= $business->email ?>.

<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Business workspace — EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css">

</head>
<body class="dashboard-page">
<header class="dashboard-header">
    <a class="brand" href="/" aria-label="EcoCycle home"><span class="brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span><span>EcoCycle</span></a>
    <a class="back-link" href="/">← Back to home</a>

    <form class="business-signout" method="POST" action="/logout"><?= csrf_field() ?><button type="submit">Sign out</button></form>
</header>
<main class="dashboard-wrap">
    <div class="dashboard-title"><div><p class="eyebrow">BUSINESS WORKSPACE</p><h1>Good morning, your business</h1><p>A snapshot of your inventory and circular activity.</p></div><span class="preview-badge">Interface preview</span></div>
    <section class="stat-grid" aria-label="Business summary">
        <article class="stat-card"><span>Tracked items</span><strong>128</strong><small>↑ 8 this month</small></article>
        <article class="stat-card"><span>Materials listed</span><strong>12</strong><small>4 enquiries received</small></article>
        <article class="stat-card"><span>Waste diverted</span><strong>86 kg</strong><small>↑ 14% this month</small></article>
        <article class="stat-card"><span>Exchange impact</span><strong>RM 1,240</strong><small>Estimated value shared</small></article>
    </section>
    <div class="dashboard-grid">
        <section class="panel"><h2>Recent inventory</h2><table class="table"><thead><tr><th>Item</th><th>Category</th><th>Quantity</th><th>Status</th></tr></thead><tbody>
            <tr><td data-label="Item">Recycled kraft boxes</td><td data-label="Category">Packaging</td><td data-label="Quantity">240 pcs</td><td data-label="Status"><span class="status">In stock</span></td></tr>
            <tr><td data-label="Item">Clean cotton offcuts</td><td data-label="Category">Textiles</td><td data-label="Quantity">18 kg</td><td data-label="Status"><span class="status">Available</span></td></tr>
            <tr><td data-label="Item">Glass jars, assorted</td><td data-label="Category">Containers</td><td data-label="Quantity">64 pcs</td><td data-label="Status"><span class="status">In stock</span></td></tr>
            <tr><td data-label="Item">Wooden shipping pallets</td><td data-label="Category">Materials</td><td data-label="Quantity">8 pcs</td><td data-label="Status"><span class="status">Listed</span></td></tr>
        </tbody></table><div class="quick-links"><a href="#inventory">+ Add inventory</a><a href="#listing">List a material</a><a href="#marketplace">Browse marketplace</a></div></section>
        <section class="panel"><h2>Recent activity</h2><div class="activity">
            <div class="activity-row"><span class="activity-icon">↗</span><span><b>New enquiry on kraft boxes</b><small>Green Corner Cafe · 2 hours ago</small></span></div>
            <div class="activity-row"><span class="activity-icon">✓</span><span><b>Material exchange completed</b><small>18 kg cotton offcuts · Yesterday</small></span></div>
            <div class="activity-row"><span class="activity-icon">▣</span><span><b>Inventory updated</b><small>Added 24 glass jars · 2 days ago</small></span></div>
        </div></section>
    </div>
</main>
<script src="/js/language-switcher.js" defer></script>
</body>
</html>
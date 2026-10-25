<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="EcoCycle helps Malaysian SMEs manage inventory, reduce waste and exchange reusable materials.">
    <title>EcoCycle — Manage smarter. Waste less.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/main/mainpage.css">
    <link rel="stylesheet" href="/css/language-switcher.css">
</head>
<body>
<div class="language-utility">
    <div class="wrap">
        <?php include resource_path('views/components/language-switcher.php'); ?>
    </div>
</div>
<header class="site-header" id="top">
    <a class="brand" href="/" aria-label="EcoCycle home"><span class="brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span><span>EcoCycle</span></a>
    <nav class="main-nav" aria-label="Main navigation">
        <a class="active" href="#home" aria-current="location">Home</a><a href="#about">About us</a><a href="#features">Features</a><a href="#how-it-works">How it works</a><a href="#marketplace">Marketplace</a><a href="#contact">Contact us</a>
    </nav>
    <div class="header-actions"><a class="button button-quiet" href="/login">Log in</a><a class="button button-dark" href="/register">Register your business <span aria-hidden="true">↗</span></a></div>
    <button class="menu-toggle" aria-label="Open navigation" onclick="document.body.classList.toggle('menu-open')">☰</button>
</header>

<main>
    <section class="hero wrap" id="home">
        <div class="hero-copy">
            <p class="eyebrow"><span></span> Smarter resources. Stronger Malaysian SMEs.</p>
            <h1>Manage smarter.<br>Waste less.<br><em>Create more<br class="desktop-break"> value.</em></h1>
            <p class="hero-description">EcoCycle helps SMEs manage inventory, monitor waste, and connect with other businesses to turn surplus materials into valuable resources.</p>
            <div class="hero-actions"><a class="button button-dark button-large" href="/register">Get started <span aria-hidden="true">↗</span></a><a class="button button-outline button-large" href="#features">Explore our features</a></div>
            <p class="location-note"><span aria-hidden="true">⌖</span> Built for businesses in Malaysia</p>
        </div>
        <div class="hero-visual">
            <img src="https://images.unsplash.com/photo-1604187351574-c75ca79f5807?auto=format&fit=crop&w=1200&q=85" alt="Reusable materials and carefully packed goods in a small business workspace">
            <div class="image-caption"><span class="caption-icon">↻</span><span><strong>A resource, not a leftover.</strong><small>Better operations. More possibilities.</small></span></div>
            <div class="image-index">01 <span>/ 03</span></div>
        </div>
    </section>

    <section class="landing-section about-section" id="about" data-node-id="32:3007">
        <div class="landing-container">
            <div class="about-intro">
                <div class="section-title"><p class="eyebrow"><img src="/images/figma/about-sprout.svg" alt=""> Purpose meets possibility</p><h2>About EcoCycle</h2></div>
                <p class="section-lead">EcoCycle is a digital platform designed to help Small and Medium Enterprises manage their daily operations more efficiently while encouraging sustainable resource use. By bringing inventory management, waste monitoring, and material exchange together in one platform, EcoCycle helps businesses reduce unnecessary waste and discover new value in surplus materials.</p>
            </div>
            <div class="purpose-grid">
                <article class="landing-card purpose-card"><span class="icon-tile"><img src="/images/figma/about-target.svg" alt=""></span><div><h3>Our Mission</h3><p>To help SMEs manage resources efficiently and reduce unnecessary material waste.</p></div></article>
                <article class="landing-card purpose-card"><span class="icon-tile"><img src="/images/figma/about-eye.svg" alt=""></span><div><h3>Our Vision</h3><p>To encourage a more sustainable business environment where useful materials are reused instead of discarded.</p></div></article>
                <article class="landing-card purpose-card"><span class="icon-tile"><img src="/images/figma/about-network.svg" alt=""></span><div><h3>Our Goal</h3><p>To connect businesses, improve stock management, and create opportunities to reuse or resell surplus materials.</p></div></article>
            </div>
        </div>
    </section>

    <section class="landing-section features-section" id="features" data-node-id="32:3039">
        <div class="landing-container">
            <div class="section-title feature-heading"><p class="eyebrow"><img src="/images/figma/features-sprout.svg" alt=""> Designed for your day-to-day</p><h2>Everything Your Business Needs in One Platform</h2></div>
            <div class="feature-grid">
                <article class="landing-card feature-card"><span class="icon-tile"><img src="/images/figma/feature-boxes.svg" alt=""></span><div><h3>Inventory Management</h3><p>Track raw materials, stock quantities, stock movements, and low-stock items in one place.</p></div></article>
                <article class="landing-card feature-card"><span class="icon-tile"><img src="/images/figma/feature-recycle.svg" alt=""></span><div><h3>Waste Management</h3><p>Record and monitor waste from raw materials and finished products to identify opportunities for waste reduction.</p></div></article>
                <article class="landing-card feature-card"><span class="icon-tile"><img src="/images/figma/feature-store.svg" alt=""></span><div><h3>Waste-to-Value Marketplace</h3><p>Connect with other businesses to sell, buy, request, or borrow surplus and reusable materials.</p></div></article>
                <article class="landing-card feature-card feature-card-wide"><span class="icon-tile"><img src="/images/figma/feature-chart.svg" alt=""></span><div><h3>Dashboard and Reports</h3><p>View stock information, waste trends, and marketplace activities to support better business decisions.</p></div></article>
                <article class="landing-card feature-card feature-card-wide"><span class="icon-tile"><img src="/images/figma/feature-business.svg" alt=""></span><div><h3>Business Management</h3><p>Register your business, manage your business profile, and access platform services after administrator approval.</p></div></article>
            </div>
        </div>
    </section>

    <section class="landing-section how-landing-section" id="how-it-works" data-node-id="32:3083">
        <div class="landing-container">
            <div class="section-title"><p class="eyebrow"><img src="/images/figma/how-sprout.svg" alt=""> A clear path forward</p><h2>How EcoCycle Works</h2></div>
            <div class="process-grid">
                <article class="process-step"><span class="step-number">01</span><img class="process-icon" src="/images/figma/how-register.svg" alt=""><h3>Register Your Business</h3><p>Create an account and upload the required business verification documents.</p></article>
                <article class="process-step"><span class="step-number">02</span><img class="process-icon" src="/images/figma/how-verified.svg" alt=""><h3>Get Verified</h3><p>Wait for the administrator to review and approve your business registration.</p></article>
                <article class="process-step"><span class="step-number">03</span><img class="process-icon" src="/images/figma/how-exchange.svg" alt=""><h3>Manage and Exchange Materials</h3><p>Manage your stock, record waste, and list or discover surplus materials through the marketplace.</p></article>
                <article class="process-step"><span class="step-number">04</span><img class="process-icon" src="/images/figma/how-chart.svg" alt=""><h3>Track Your Progress</h3><p>Monitor transactions, review stock and waste reports, and identify opportunities to use resources more efficiently.</p></article>
            </div>
        </div>
    </section>

    <section class="landing-section marketplace-section" id="marketplace" data-node-id="32:3124">
        <div class="landing-container">
            <div class="section-title marketplace-heading"><p class="eyebrow"><img src="/images/figma/market-sprout.svg" alt=""> The waste-to-value exchange</p><h2>Give Surplus Materials a Second Life</h2><p class="section-subtitle">Discover reusable materials offered by other businesses and find opportunities to reduce material costs.</p></div>
            <div class="demo-notice"><img src="/images/figma/market-info.svg" alt=""><span>Demonstration listings — sample data only</span></div>
            <div class="market-grid">
                <article class="material-card"><img class="material-photo" src="/images/figma/market-boards.png" alt="Reclaimed wooden boards stacked in a workshop"><div class="material-details"><p class="material-location"><img src="/images/figma/market-pin.svg" alt=""> Shah Alam, Selangor</p><h3>Reclaimed Wooden Boards</h3><div class="material-availability"><span>80 boards available</span><small>Used · Good condition</small></div><div class="material-price"><strong>RM 12 / board</strong><span>Sample</span></div></div></article>
                <article class="material-card"><img class="material-photo" src="/images/figma/market-cardboard.png" alt="Clean recyclable cardboard boxes in a workshop"><div class="material-details"><p class="material-location"><img src="/images/figma/market-pin.svg" alt=""> Petaling Jaya, Selangor</p><h3>Recyclable Cardboard Boxes</h3><div class="material-availability"><span>200 boxes available</span><small>Used · Clean and dry</small></div><div class="material-price"><strong>RM 1.50 / box</strong><span>Sample</span></div></div></article>
                <article class="material-card"><img class="material-photo" src="/images/figma/market-packaging.png" alt="Surplus cardboard packaging rolls on a workbench"><div class="material-details"><p class="material-location"><img src="/images/figma/market-pin.svg" alt=""> Johor Bahru, Johor</p><h3>Surplus Packaging Materials</h3><div class="material-availability"><span>25 rolls available</span><small>Unused · Original stock</small></div><div class="material-price"><strong>RM 18 / roll</strong><span>Sample</span></div></div></article>
                <article class="material-card"><img class="material-photo" src="/images/figma/market-textiles.png" alt="Reusable folded textiles in a small business workshop"><div class="material-details"><p class="material-location"><img src="/images/figma/market-pin.svg" alt=""> George Town, Penang</p><h3>Reusable Production Materials</h3><div class="material-availability"><span>40 kg available</span><small>Offcuts · Sorted and clean</small></div><div class="material-price"><strong>Free</strong><span>Sample</span></div></div></article>
            </div>
            <div class="market-access"><a class="button button-dark button-large" href="/login">Explore Marketplace <img src="/images/figma/market-arrow.svg" alt=""></a><p><img src="/images/figma/market-lock.svg" alt=""> Login or register your business to explore. Marketplace access is available to approved business accounts only.</p></div>
        </div>
    </section>

    <section class="landing-section impact-section" id="impact" data-node-id="32:3203">
        <div class="landing-container impact-layout">
            <div class="impact-copy"><div class="section-title"><p class="eyebrow"><img src="/images/figma/impact-sprout.svg" alt=""> Better for business. Better for resources.</p><h2>Small Changes. Meaningful Impact.</h2></div>
                <div class="impact-benefits">
                    <article class="landing-card impact-card"><img src="/images/figma/impact-leaf.svg" alt=""><div><h3>Reduce Material Waste</h3><p>Identify reusable materials and avoid unnecessary disposal.</p></div></article>
                    <article class="landing-card impact-card"><img src="/images/figma/impact-wallet.svg" alt=""><div><h3>Improve Cost Efficiency</h3><p>Manage inventory better and discover affordable surplus materials.</p></div></article>
                    <article class="landing-card impact-card"><img src="/images/figma/impact-repeat.svg" alt=""><div><h3>Encourage Resource Reuse</h3><p>Connect businesses to give leftover materials another useful purpose.</p></div></article>
                </div>
            </div>
            <div class="cycle-panel" aria-label="Resources cycle from businesses to materials, reuse, and less waste">
                <div class="cycle-illustration"><img class="cycle-loop" src="/images/figma/impact-resource-loop.png" alt=""><img class="cycle-arrow cycle-arrow-one" src="/images/figma/impact-arrow-down-right.svg" alt=""><img class="cycle-arrow cycle-arrow-two" src="/images/figma/impact-arrow-up-left.svg" alt="">
                    <div class="cycle-stage cycle-business"><img src="/images/figma/impact-building.svg" alt=""><span>Businesses</span></div><div class="cycle-stage cycle-waste"><img src="/images/figma/impact-sprout-stage.svg" alt=""><span>Less waste</span></div><div class="cycle-stage cycle-materials"><img src="/images/figma/impact-boxes.svg" alt=""><span>Materials</span></div><div class="cycle-stage cycle-reuse"><img src="/images/figma/impact-repeat-stage.svg" alt=""><span>Reuse</span></div>
                    <div class="cycle-center"><img src="/images/figma/impact-recycle.svg" alt=""><span>Value, recirculated.</span></div>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-section final-cta" id="contact" data-node-id="32:3257">
        <img class="cta-ring cta-ring-top" src="/images/figma/cta-ring.svg" alt=""><img class="cta-ring cta-ring-bottom" src="/images/figma/cta-ring.svg" alt="">
        <div class="cta-content"><img class="cta-sprout" src="/images/figma/cta-sprout.svg" alt=""><h2>Ready to Make Your Business More Resource-Efficient?</h2><p>Join EcoCycle to manage your resources, reduce unnecessary waste, and explore new opportunities for material reuse.</p><div class="cta-actions"><a class="button cta-primary" href="/register">Register Your Business <img src="/images/figma/cta-arrow.svg" alt=""></a><a class="button cta-secondary" href="/login">Login to EcoCycle</a></div></div>
    </section>
</main>
<footer class="site-footer"><a class="brand" href="/"><span class="brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span><span>EcoCycle</span></a><span>Better resources. Better business.</span><span class="footer-links"><a href="/login">Sign in</a></span><span>© 2026 EcoCycle Malaysia</span></footer>
<script src="/js/main-nav.js" defer></script>
<script src="/js/language-switcher.js" defer></script>
</body>
</html>

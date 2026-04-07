<?php
require_once __DIR__ . '/../functions.php';
$page_title = 'About Us';
include __DIR__ . '/../inc_header.php';
?>

<div class="legal-page-wrap about-page">

    <div class="legal-page-header">
        <h2 class="legal-page-title">About London Labels</h2>
        <p class="legal-page-meta">Style Without Borders</p>
    </div>

    <div class="legal-page-body">

        <p class="legal-intro">
            London Labels is a curated fashion and lifestyle destination born in Lagos and inspired by London.
            We believe that where you live should never limit how well you dress — and we built this brand
            to prove it.
        </p>

        <h3>Who We Are</h3>
        <p>
            We are a Lagos-based retail business with a physical store at Treasure Mall, Ajah — and an online
            store that reaches customers across Nigeria. Every product we carry is hand-picked and sourced
            directly from the United Kingdom, brought into Nigeria, and made available to you from our Lagos
            store. No middlemen. No compromises on quality.
        </p>
        <p>
            Our customers are style-conscious, globally minded, and done settling for less. They want
            authentic pieces with real provenance — not imitations. That is exactly what we deliver.
        </p>

        <h3>Our Story</h3>
        <p>
            London Labels started as a physical store with a simple idea: bring the best of London's fashion
            scene to Lagos. What we found was that the demand was far bigger than one store could serve.
            Customers from Abuja, Port Harcourt, Kano, and beyond were asking for the same thing — access
            to authentic, internationally sourced fashion without having to travel or pay inflated import
            prices.
        </p>
        <p>
            So we built this. An online store that operates with the same care and curation as our physical
            location, delivering across Nigeria from our base in Ajah, Lagos. The store you can walk into.
            The website you can shop from anywhere.
        </p>

        <h3>What We Stand For</h3>
        <div class="about-values">
            <div class="about-value">
                <strong>Authenticity</strong>
                <p>Every item is sourced directly from the UK. We do not stock what we cannot verify.</p>
            </div>
            <div class="about-value">
                <strong>Accessibility</strong>
                <p>Premium fashion should not require a flight to London. We bring it to your door across Nigeria.</p>
            </div>
            <div class="about-value">
                <strong>Accountability</strong>
                <p>Real support, real people, real responses. We stand behind every order we fulfil.</p>
            </div>
        </div>

        <h3>Visit Us</h3>
        <p>
            Our physical store is open in Lagos. Come in, browse the collection, and speak to our team directly.
        </p>
        <p>
            <?= e(STORE_ADDRESS) ?><br>
            <a href="<?= e(STORE_MAP_URL) ?>" target="_blank" rel="noopener noreferrer">Open in Google Maps</a>
        </p>

        <h3>Shop Online</h3>
        <p>
            Browse our full collection and place your order online. We deliver statewide within Lagos and
            nationwide across Nigeria. Delivery fees and estimated times are shown at checkout.
        </p>
        <p>
            <a href="<?= BASE_URL ?>/shop.php">Browse the collection</a> &nbsp;&middot;&nbsp;
            <a href="<?= BASE_URL ?>/contact.php">Get in touch</a>
        </p>

        <?php if (defined('WHATSAPP_GROUP_URL') && WHATSAPP_GROUP_URL !== ''): ?>
        <h3>Join the Community</h3>
        <p>
            Follow us on
            <?php if (defined('YOUTUBE_CHANNEL_URL') && YOUTUBE_CHANNEL_URL !== ''): ?>
                <a href="<?= e(YOUTUBE_CHANNEL_URL) ?>" target="_blank" rel="noopener noreferrer">YouTube</a> for
                style guides, product reviews, and new arrivals — and join our
            <?php else: ?>
                our
            <?php endif; ?>
            <a href="<?= e(WHATSAPP_GROUP_URL) ?>" target="_blank" rel="noopener noreferrer">WhatsApp community</a>
            to stay close to the brand.
        </p>
        <?php elseif (defined('YOUTUBE_CHANNEL_URL') && YOUTUBE_CHANNEL_URL !== ''): ?>
        <h3>Follow Us</h3>
        <p>
            Watch us on <a href="<?= e(YOUTUBE_CHANNEL_URL) ?>" target="_blank" rel="noopener noreferrer">YouTube</a>
            for style guides, product reviews, and new arrivals.
        </p>
        <?php endif; ?>

    </div>
</div>

<?php include __DIR__ . '/../inc_footer.php'; ?>

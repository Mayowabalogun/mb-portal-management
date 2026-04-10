<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/public/assets/css/home.css" rel="stylesheet">
</head>
<body>
<header class="main-header">
    <div class="header-upper">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="logo fw-bold fs-4">MB Real Estate</div>
                </div>

                <div class="col-lg-6 col-md-4 d-none d-md-block">
                    <nav class="main-menu">
                        <ul class="navigation">
                            <li class="current"><a href="index.php">Home</a></li>
                            <li class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Portals</a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="login.php?portal=tenant"><span class="portal-icon tenant"><i class="fas fa-user"></i></span>Tenant Portal</a></li>
                                    <li><a class="dropdown-item" href="login.php?portal=landlord"><span class="portal-icon landlord"><i class="fas fa-building"></i></span>Landlord Portal</a></li>
                                    <li><a class="dropdown-item" href="login.php?portal=vendor"><span class="portal-icon vendor"><i class="fas fa-tools"></i></span>Vendor Portal</a></li>
                                    <li><a class="dropdown-item" href="login.php?portal=partner"><span class="portal-icon partner"><i class="fas fa-handshake"></i></span>Partner Portal</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="login.php?portal=staff"><span class="portal-icon staff"><i class="fas fa-user-tie"></i></span>Staff Portal</a></li>
                                    <li><a class="dropdown-item" href="login.php?portal=admin"><span class="portal-icon admin"><i class="fas fa-shield-alt"></i></span>Admin Portal</a></li>
                                </ul>
                            </li>
                            <li><a href="login.php" class="rent-portal-link">Rent Portal</a></li>
                            <li><a href="contact.php">Contact Us</a></li>
                        </ul>
                    </nav>
                </div>

                <div class="col-lg-3 col-md-4 col-6 text-end">
                    <a class="btn btn-primary btn-sm" href="login.php">Unified Login</a>
                </div>
            </div>
        </div>
    </div>

    <div class="collapse d-md-none" id="mobileMenu">
        <div class="container py-3 border-top">
            <ul class="list-unstyled mb-0">
                <li class="py-2"><a href="index.php" class="text-decoration-none text-dark">Home</a></li>
                <li class="py-2"><strong class="text-muted small text-uppercase">Portals</strong></li>
                <li class="py-2 ps-3"><a href="login.php?portal=tenant" class="text-decoration-none text-dark">Tenant Portal</a></li>
                <li class="py-2 ps-3"><a href="login.php?portal=landlord" class="text-decoration-none text-dark">Landlord Portal</a></li>
                <li class="py-2 ps-3"><a href="login.php?portal=vendor" class="text-decoration-none text-dark">Vendor Portal</a></li>
                <li class="py-2 ps-3"><a href="login.php?portal=partner" class="text-decoration-none text-dark">Partner Portal</a></li>
                <li class="py-2 ps-3"><a href="login.php?portal=staff" class="text-decoration-none text-dark">Staff Portal</a></li>
                <li class="py-2 ps-3"><a href="login.php?portal=admin" class="text-decoration-none text-dark">Admin Portal</a></li>
                <li class="py-2 border-top mt-2"><a href="login.php" class="text-decoration-none text-dark">Unified Login</a></li>
            </ul>
        </div>
    </div>
</header>

<section class="video-banner-section">
    <div class="container">
        <div class="inner-container">
            <h1 class="variable-text" id="typewriter"></h1>
            <div class="text"><strong>MB Real Estate Agency</strong>. We are smart enough to meet your demand.</div>
            <div class="mt-4">
                <a href="contact.php" class="theme-btn"><i class="fas fa-phone-alt me-2"></i>Contact Us Now</a>
                <a href="verify_tenant_code.php" class="theme-btn btn-style-two"><i class="fas fa-user-check me-2"></i>Tenant Verification</a>
            </div>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-md-3 col-6"><div class="stat-item"><i class="fas fa-building"></i><h3><?= number_format($stats['flats']) ?></h3><p>Available Apartments</p></div></div>
            <div class="col-md-3 col-6"><div class="stat-item"><i class="fas fa-bed"></i><h3><?= number_format($stats['hostels']) ?></h3><p>Hostel Spaces</p></div></div>
            <div class="col-md-3 col-6"><div class="stat-item"><i class="fas fa-store"></i><h3><?= number_format($stats['shops']) ?></h3><p>Shops & Commercial</p></div></div>
            <div class="col-md-3 col-6"><div class="stat-item"><i class="fas fa-users"></i><h3><?= number_format($stats['tenants']) ?></h3><p>Happy Tenants</p></div></div>
        </div>
    </div>
</section>

<section class="appointment-section">
    <div class="container">
        <div class="sec-title">
            <h1>AVAILABLE PROPERTIES FOR RENT</h1>
            <div class="separator"></div>
            <p class="text-muted mt-3">Discover your perfect space from our premium listings</p>
        </div>

        <ul class="nav nav-pills justify-content-center mb-5" id="propertyTabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#flats" type="button"><i class="fas fa-building me-2"></i>Apartments</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#hostels" type="button"><i class="fas fa-bed me-2"></i>Hostels</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#shops" type="button"><i class="fas fa-store me-2"></i>Shops</button></li>
        </ul>

        <div class="tab-content" id="propertyTabsContent">
            <div class="tab-pane fade show active" id="flats" role="tabpanel">
                <div class="row">
                    <?php if (empty($availableFlats)): ?>
                        <div class="col-12 text-center py-5"><i class="fas fa-home fa-3x text-muted mb-3"></i><p class="text-muted">No available apartments at the moment. Check back soon!</p></div>
                    <?php else: foreach ($availableFlats as $flat): ?>
                        <div class="col-lg-4 col-md-6 mb-4"><div class="property-card"><div class="property-image"><span class="property-badge">Available</span><i class="fas fa-building"></i></div><div class="property-content"><h4><?= htmlspecialchars($flat['property_label']) ?></h4><div class="property-location"><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($flat['address'] . ', ' . $flat['town_city']) ?></div><div class="property-price"><?= HomeService::formatNaira((float) $flat['rent_amount']) ?> <small>/year</small></div><a href="property-details.php?id=<?= (int) $flat['unit_id'] ?>" class="theme-btn w-100 justify-content-center">View Details</a></div></div></div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <div class="tab-pane fade" id="hostels" role="tabpanel">
                <div class="row">
                    <?php if (empty($availableHostels)): ?>
                        <div class="col-12 text-center py-5"><i class="fas fa-bed fa-3x text-muted mb-3"></i><p class="text-muted">No available hostel spaces at the moment.</p></div>
                    <?php else: foreach ($availableHostels as $hostel): ?>
                        <div class="col-lg-4 col-md-6 mb-4"><div class="property-card"><div class="property-image hostel-bg"><span class="property-badge">Student Friendly</span><i class="fas fa-bed"></i></div><div class="property-content"><h4><?= htmlspecialchars($hostel['property_label']) ?></h4><div class="property-location"><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($hostel['address']) ?></div><div class="property-price"><?= HomeService::formatNaira((float) $hostel['rent_amount']) ?> <small>/session</small></div><a href="property-details.php?id=<?= (int) $hostel['unit_id'] ?>" class="theme-btn w-100 justify-content-center">View Details</a></div></div></div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <div class="tab-pane fade" id="shops" role="tabpanel">
                <div class="row">
                    <?php if (empty($availableShops)): ?>
                        <div class="col-12 text-center py-5"><i class="fas fa-store fa-3x text-muted mb-3"></i><p class="text-muted">No available shops at the moment.</p></div>
                    <?php else: foreach ($availableShops as $shop): ?>
                        <div class="col-lg-4 col-md-6 mb-4"><div class="property-card"><div class="property-image shop-bg"><span class="property-badge">Commercial</span><i class="fas fa-store"></i></div><div class="property-content"><h4><?= htmlspecialchars($shop['property_label']) ?></h4><div class="property-location"><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($shop['address']) ?></div><div class="property-price"><?= HomeService::formatNaira((float) $shop['rent_amount']) ?> <small>/year</small></div><a href="property-details.php?id=<?= (int) $shop['unit_id'] ?>" class="theme-btn w-100 justify-content-center">View Details</a></div></div></div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <div class="booking-form-section">
            <div class="sec-title">
                <h1>Book a Viewing</h1>
                <div class="separator"></div>
                <p class="text-muted mt-3">Schedule a property tour with our agents</p>
            </div>
            <form method="post" action="booking-submit.php" class="default-form">
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><input type="text" name="name" placeholder="Your Full Name *" required></div></div>
                    <div class="col-md-6"><div class="form-group"><input type="email" name="email" placeholder="Email Address *" required></div></div>
                    <div class="col-md-6"><div class="form-group"><input type="tel" name="phone" placeholder="Phone Number *" required></div></div>
                    <div class="col-md-6"><div class="form-group"><select name="property_type" class="form-select" required><option value="">Select Property Type</option><option value="flat">Apartment/Flat</option><option value="hostel">Hostel</option><option value="shop">Shop/Commercial</option></select></div></div>
                    <div class="col-12"><div class="form-group"><textarea name="message" placeholder="Your Message or Specific Requirements"></textarea></div></div>
                    <div class="col-12 text-center"><button type="submit" class="theme-btn"><i class="fas fa-paper-plane me-2"></i>Submit Booking Request</button></div>
                </div>
            </form>
        </div>
    </div>
</section>

<footer class="main-footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4"><div class="footer-widget"><h4>About MB Real Estate</h4><p class="text-white-50">We are MB REAL ESTATE AGENCY registered with Corporate Affairs Commission to provide all Real Estate services, construction and supply. We employ personalized service methods to meet the unique needs of all our clients.</p><a href="about.php" class="theme-btn btn-sm">Learn More</a></div></div>
            <div class="col-lg-2 col-md-6 mb-4"><div class="footer-widget"><h4>Portals</h4><ul><li><a href="login.php?portal=tenant">Tenant Portal</a></li><li><a href="login.php?portal=landlord">Landlord Portal</a></li><li><a href="login.php?portal=vendor">Vendor Portal</a></li><li><a href="login.php?portal=partner">Partner Portal</a></li><li><a href="login.php?portal=admin">Admin Portal</a></li></ul></div></div>
            <div class="col-lg-2 col-md-6 mb-4"><div class="footer-widget"><h4>Properties</h4><ul><li><a href="properties.php?type=flat">Apartments</a></li><li><a href="properties.php?type=hostel">Hostels</a></li><li><a href="properties.php?type=shop">Shops</a></li><li><a href="properties.php">All Listings</a></li></ul></div></div>
            <div class="col-lg-4 col-md-6 mb-4"><div class="footer-widget"><h4>Contact Info</h4><ul class="contact-info"><li><i class="fas fa-map-marker-alt"></i><span>No 51 Hospital Road Brita View Building,<br>Beside NEPA Market, Akure, Ondo State.</span></li><li><i class="fas fa-phone"></i><span>+234 806 664 3205</span></li><li><i class="fas fa-envelope"></i><span>info@rent.mbpropertyfinder.com</span></li><li><i class="fas fa-clock"></i><span>Week Days: 08:00 AM - 05:00 PM<br>Saturday: 10:00 AM<br>Sunday: Closed</span></li></ul></div></div>
        </div>
        <div class="border-top border-secondary pt-4 mt-4 text-center"><p class="text-white-50 mb-0">&copy; 2026 MB Real Estate Agency. All Rights Reserved.</p></div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/typeit@8.7.1/dist/index.umd.min.js"></script>
<script src="<?= BASE_URL ?>/public/assets/js/home.js"></script>
</body>
</html>

/* ====================================================
   WALUYA — Landing Page JavaScript
   ==================================================== */

document.addEventListener('DOMContentLoaded', function () {

    // ── Initialize Lucide Icons ──
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // ── Navbar Scroll Effect ──
    const navbar = document.getElementById('navbar');
    let lastScrollY = 0;

    function handleNavbarScroll() {
        const currentScrollY = window.scrollY;

        if (currentScrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }

        lastScrollY = currentScrollY;
    }

    window.addEventListener('scroll', handleNavbarScroll, { passive: true });

    // ── Hamburger Menu ──
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('navMenu');

    if (hamburger && navMenu) {
        function closeMobileMenu() {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
            document.body.style.overflow = '';
        }

        hamburger.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = navMenu.classList.toggle('active');
            hamburger.classList.toggle('active');
            document.body.style.overflow = isOpen ? 'hidden' : '';
        });

        // Close menu when clicking any nav link or mobile login button
        const menuInteractiveLinks = navMenu.querySelectorAll('.nav-link, .btn-mobile-login');
        menuInteractiveLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                closeMobileMenu();
            });
        });

        // Close menu when clicking outside navbar
        document.addEventListener('click', function (e) {
            if (navMenu.classList.contains('active')) {
                const navbarEl = document.getElementById('navbar');
                if (navbarEl && !navbarEl.contains(e.target)) {
                    closeMobileMenu();
                }
            }
        });

        // Close menu on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && navMenu.classList.contains('active')) {
                closeMobileMenu();
            }
        });

        // Close menu on resize if open
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });
    }

    // ── Active Nav Link on Scroll ──
    const sections = document.querySelectorAll('section[id]');
    const allNavLinks = document.querySelectorAll('.nav-link');

    function highlightActiveLink() {
        const scrollY = window.scrollY + 120;

        sections.forEach(function (section) {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            const sectionId = section.getAttribute('id');

            if (scrollY >= sectionTop && scrollY < sectionTop + sectionHeight) {
                allNavLinks.forEach(function (link) {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === '#' + sectionId) {
                        link.classList.add('active');
                    }
                });
            }
        });
    }

    window.addEventListener('scroll', highlightActiveLink, { passive: true });

    // ── Scroll Reveal (Fade In Up) ──
    const fadeElements = document.querySelectorAll('.fade-in-up');

    function revealOnScroll() {
        const windowHeight = window.innerHeight;
        const triggerPoint = windowHeight * 0.88;

        fadeElements.forEach(function (el) {
            const elementTop = el.getBoundingClientRect().top;

            if (elementTop < triggerPoint) {
                el.classList.add('visible');
            }
        });
    }

    // Run on load & scroll
    revealOnScroll();
    window.addEventListener('scroll', revealOnScroll, { passive: true });

    // ── Smooth Scroll for Anchor Links ──
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // ── FAQ Accordion ──
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(function (item) {
        const questionBtn = item.querySelector('.faq-question');
        if (!questionBtn) return;

        questionBtn.addEventListener('click', function () {
            const isActive = item.classList.contains('active');

            // Close other items for a clean accordion effect
            faqItems.forEach(function (otherItem) {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                    const otherBtn = otherItem.querySelector('.faq-question');
                    if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
                }
            });

            // Toggle current item
            if (isActive) {
                item.classList.remove('active');
                questionBtn.setAttribute('aria-expanded', 'false');
            } else {
                item.classList.add('active');
                questionBtn.setAttribute('aria-expanded', 'true');
            }
        });
    });

});

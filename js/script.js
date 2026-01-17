/**
 * Infra Salama - Main JavaScript File
 * Author: Infra Salama
 * Version: 1.0
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Initialize AOS Animation Library
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true,
        mirror: false
    });

    // Navbar Scroll Effect
    const navbar = document.querySelector('.navbar');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.style.padding = '0.5rem 0';
            navbar.style.backgroundColor = 'rgba(0, 86, 179, 0.95)';
        } else {
            navbar.style.padding = '1rem 0';
            navbar.style.backgroundColor = 'rgba(0, 86, 179, 0.9)';
        }
    });

    // Back to Top Button
    const backToTopButton = document.getElementById('backToTop');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            backToTopButton.classList.add('active');
        } else {
            backToTopButton.classList.remove('active');
        }
    });
    
    backToTopButton.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Form Validation and Submission
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = contactForm.querySelector('button[type="submit"]');
            const privacyCheckbox = contactForm.querySelector('input[name="privacy"]');
            const formData = new FormData(contactForm);

            if (privacyCheckbox) {
                formData.set('privacy', privacyCheckbox.checked ? 'on' : '');
            }

            try {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Envoi...';
                }

                const endpoint = contactForm.getAttribute('action') || 'api/contact.php';
                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (response.ok && result.success) {
                    alert(result.message || 'Votre message a été envoyé avec succès.');
                    contactForm.reset();
                } else {
                    const message = result.errors ? result.errors.join('\n') : (result.message || 'Erreur lors de l\'envoi');
                    alert(message);
                }
            } catch (error) {
                console.error('Erreur contact:', error);
                alert('Erreur de connexion au serveur');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Envoyer le message';
                }
            }
        });
    }

    // Quote Form Validation and Submission
    const quoteForm = document.getElementById('quoteForm');
    if (quoteForm) {
        quoteForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = quoteForm.querySelector('button[type="submit"]');
            const privacyCheckbox = quoteForm.querySelector('input[name="privacy"]');
            const formData = new FormData(quoteForm);

            if (privacyCheckbox) {
                formData.set('privacy', privacyCheckbox.checked ? 'on' : '');
            }

            try {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Envoi...';
                }

                const endpoint = quoteForm.getAttribute('action') || 'api/devis.php';
                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (response.ok && result.success) {
                    alert(result.message || 'Votre demande de devis a été envoyée avec succès.');
                    quoteForm.reset();
                } else {
                    const message = result.errors ? result.errors.join('\n') : (result.message || 'Erreur lors de l\'envoi');
                    alert(message);
                }
            } catch (error) {
                console.error('Erreur devis:', error);
                alert('Erreur de connexion au serveur');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Envoyer la demande de devis';
                }
            }
        });
    }

    // Testimonials Slider (if needed)
    // This is a placeholder for a testimonial slider functionality
    // You can implement a slider library like Swiper.js or Slick Slider here
    // Example implementation:
    /*
    const testimonialsSlider = new Swiper('.testimonials-slider', {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        breakpoints: {
            768: {
                slidesPerView: 2,
            },
            992: {
                slidesPerView: 3,
            }
        }
    });
    */

    // Newsletter Form Submission
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const emailInput = this.querySelector('input[type="email"]');
            const email = emailInput.value;
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Veuillez entrer une adresse email valide.');
                return;
            }
            
            // Simulate form submission
            alert('Merci de vous être abonné à notre newsletter!');
            emailInput.value = '';
        });
    }

    // Animate on scroll for elements
    const animateElements = document.querySelectorAll('.animate-on-scroll');
    if (animateElements.length > 0) {
        const animateElementsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    animateElementsObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });

        animateElements.forEach(element => {
            animateElementsObserver.observe(element);
        });
    }

    // Initialize tooltips if Bootstrap 5 is used
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (tooltipTriggerList.length > 0) {
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Mobile menu toggle enhancement
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbarToggler && navbarCollapse) {
        document.addEventListener('click', function(e) {
            const isClickInside = navbarToggler.contains(e.target) || navbarCollapse.contains(e.target);
            
            if (!isClickInside && navbarCollapse.classList.contains('show')) {
                navbarToggler.click();
            }
        });
    }

    console.log('Infra Salama - All scripts loaded successfully!');
});

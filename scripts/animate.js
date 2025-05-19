// Fungsi untuk menambahkan class animasi saat elemen terlihat
function animateOnScroll() {
    const sections = document.querySelectorAll('.animate');

    const observerOptions = {
        root: null, // Gunakan viewport sebagai root
        rootMargin: '0px', // Tidak ada margin
        threshold: 0.3 // Trigger saat 30% elemen terlihat
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const section = entry.target;

                // Tambahkan class animasi sesuai dengan data-animation
                const animationType = section.getAttribute('data-animation');
                section.classList.add(animationType);

                // Hentikan observasi setelah animasi dijalankan
                observer.unobserve(section);
            }
        });
    }, observerOptions);

    // Observasi setiap section
    sections.forEach(section => {
        observer.observe(section);
    });
}

// Jalankan fungsi animateOnScroll saat halaman dimuat
window.addEventListener('load', animateOnScroll);

// Add this to your existing animate.js file or create a new JS file
document.addEventListener('DOMContentLoaded', function() {
    // Parallax effect for hero section
    const heroSection = document.querySelector('.hero');
    const heroBackground = document.querySelector('.hero-bg');
    
    if (heroSection && heroBackground) {
        window.addEventListener('scroll', function() {
            const scrollPosition = window.scrollY;
            if (scrollPosition < window.innerHeight) {
                // Create parallax effect on background
                heroBackground.style.transform = `translateY(${scrollPosition * 0.3}px)`;
                
                // Fade out hero content on scroll
                const opacity = 1 - (scrollPosition / (window.innerHeight * 0.5));
                heroSection.style.opacity = opacity > 0 ? opacity : 0;
            }
        });
    }
    
    // Add scroll reveal animation for sections
    const animateElements = document.querySelectorAll('.animate');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const animation = entry.target.getAttribute('data-animation');
                if (animation) {
                    entry.target.classList.add(animation);
                    observer.unobserve(entry.target);
                }
            }
        });
    }, {
        threshold: 0.1
    });
    
    animateElements.forEach(element => {
        observer.observe(element);
    });
});
let slideIndex = 0;

function showSlide(index) {
    const slides = document.querySelector('.slides');
    const totalSlides = document.querySelectorAll('.slide').length;

    if (index >= totalSlides) {
        slideIndex = 0;
    } else if (index < 0) {
        slideIndex = totalSlides - 1;
    } else {
        slideIndex = index;
    }

    slides.style.transform = `translateX(-${slideIndex * 100}%)`;
}

document.querySelector('.prev').addEventListener('click', () => {
    showSlide(slideIndex - 1);
});

document.querySelector('.next').addEventListener('click', () => {
    showSlide(slideIndex + 1);
});

setInterval(() => {
    showSlide(slideIndex + 1);
}, 5000);
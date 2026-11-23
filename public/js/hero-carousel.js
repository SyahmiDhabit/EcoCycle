(() => {
    const carousel = document.querySelector('[data-hero-carousel]');

    if (!carousel) return;

    const photos = [...carousel.querySelectorAll('img')];
    const controls = carousel.querySelector('.hero-carousel-controls');
    const counter = carousel.querySelector('[data-slide-number]');
    let currentSlide = 0;

    const moveSlide = (step) => {
        photos[currentSlide].hidden = true;

        currentSlide = (currentSlide + step + photos.length) % photos.length;
        photos[currentSlide].hidden = false;
        counter.textContent = String(currentSlide + 1).padStart(2, '0');
    };

    controls.querySelectorAll('[data-slide-step]').forEach((button) => {
        button.addEventListener('click', () => moveSlide(Number(button.dataset.slideStep)));
    });

    controls.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;

        event.preventDefault();
        moveSlide(event.key === 'ArrowLeft' ? -1 : 1);
    });

    controls.hidden = false;
})();

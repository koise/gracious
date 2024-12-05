document.addEventListener("DOMContentLoaded", function () {

    var typed = new Typed(".typing", {
        strings: ["Welcome!","Gracious Clinic", "At your service"],
        typeSpeed: 50,
        backSpeed: 60,
        loop: true
    });

    const toTop = document.querySelector('.to-top');
const area1 = document.querySelector('.area:nth-child(1)');
const area2 = document.querySelector('.area:nth-child(2)');
const area3 = document.querySelector('.area:nth-child(3)');
const area4 = document.querySelector('.area:nth-child(4)');
// Function to update the button's state
function updateToTopButton() {
    if (area2.classList.contains('show') || area3.classList.contains('show')
    || area4.classList.contains('show')) {
        toTop.classList.add('active');
    } else if (area1.classList.contains('show')) {
        toTop.classList.remove('active');
    }
}

// Observe mutations for Area 1 and Area 2
const observers = new MutationObserver(() => {
    updateToTopButton();
});

observers.observe(area1, { attributes: true, attributeFilter: ['class'] });
observers.observe(area2, { attributes: true, attributeFilter: ['class'] });
observers.observe(area3, { attributes: true, attributeFilter: ['class'] });
observers.observe(area4, { attributes: true, attributeFilter: ['class'] });

toTop.addEventListener('click', () => {
    area1.scrollIntoView({
        behavior: 'smooth',
    });
});
    // Scroll Reveal for Sections
    const hiddenElements = document.querySelectorAll('.area');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
            } else {
                entry.target.classList.remove('show');
            }
        });
    });
    hiddenElements.forEach((el) => observer.observe(el));

    // Remove Hash from URL After Click
    window.addEventListener('hashchange', () => {
        history.replaceState(null, null, location.pathname + location.search);
    });

    // Carousel Logic
    const cards = document.querySelectorAll('.card');
    const prevButton = document.getElementById('prev');
    const nextButton = document.getElementById('next');
    let currentIndex = 0;

    function updateCarousel() {
        cards.forEach((card, index) => {
          if (index === currentIndex) {
            card.style.transform = "translateX(0) scale(1)";
            card.style.opacity = "1";
            card.style.zIndex = "1";
            card.style.filter = "none"; // Remove blur effect for the current card
          } else if (index === (currentIndex - 1 + cards.length) % cards.length) {
            card.style.transform = "translateX(-80%) translateY(20%) scale(0.8)";
            card.style.opacity = "0.4";
            card.style.zIndex = "0";
            card.style.filter = "blur(2px)";  // Remove blur effect for the previous card
          } else if (index === (currentIndex + 1) % cards.length) {
            card.style.transform = "translateX(80%) translateY(20%) scale(0.8)";
            card.style.opacity = "0.4";
            card.style.zIndex = "0";
            card.style.filter = "blur(2px)"; // Apply blur effect to the next card
          } else {
            card.style.opacity = "0";
            card.style.filter = "none"; // Remove blur effect for hidden cards
          }
        });
      }
    prevButton.addEventListener('click', () => {
        currentIndex = (currentIndex - 1 + cards.length) % cards.length;
        updateCarousel();
    });

    nextButton.addEventListener('click', () => {
        currentIndex = (currentIndex + 1) % cards.length;
        updateCarousel();
    });

    // Initialize the carousel
    updateCarousel();

    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
      const question = item.querySelector('.faq-question');
      const answer = item.querySelector('.faq-answer');
      const arrow = item.querySelector('.arrow');

      question.addEventListener('click', () => {
        const isOpen = answer.classList.contains('open');

        // Close all open answers
        document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('open'));
        document.querySelectorAll('.arrow').forEach(a => a.classList.remove('open'));

        // Toggle current item
        if (!isOpen) {
          answer.classList.add('open');
          arrow.classList.add('open');
        }
      });
    });
});

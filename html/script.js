const savedTheme = localStorage.getItem('theme');
if (savedTheme === 'dark') {
    document.body.classList.remove('light');
    document.body.classList.add('dark');
}

const themeBtn = document.getElementById('theme-btn');
themeBtn.addEventListener('click', () => {
    if (document.body.classList.contains('light')) {
        document.body.classList.replace('light', 'dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.body.classList.replace('dark', 'light');
        localStorage.setItem('theme', 'light');
    }
});

const banner = document.getElementById('notification-banner');
const closeBanner = document.getElementById('close-banner');
closeBanner.addEventListener('click', () => {
    banner.classList.add('hidden');
});

const hamburgerBtn = document.getElementById('hamburger-btn');
const navMenu = document.getElementById('nav-menu');
hamburgerBtn.addEventListener('click', () => {
    navMenu.classList.toggle('show');
});

const openModalBtn = document.getElementById('open-modal-btn');
const closeModalBtn = document.getElementById('close-modal-btn');
const modalContainer = document.getElementById('modal-container');
openModalBtn.addEventListener('click', () => {
    modalContainer.classList.remove('hidden');
});
closeModalBtn.addEventListener('click', () => {
    modalContainer.classList.add('hidden');
});
const faqQuestions = document.querySelectorAll('.faq-question');
faqQuestions.forEach(question => {
    question.addEventListener('click', () => {
        const answer = question.nextElementSibling;
        answer.classList.toggle('hidden');
    });
});

const slides = document.querySelectorAll('.slide');
const prevBtn = document.getElementById('prev-btn');
const nextBtn = document.getElementById('next-btn');
let currentSlideIndex = 0;

function showSlide(index) {
    slides.forEach(slide => slide.classList.add('hidden'));
    slides[index].classList.remove('hidden');
}

prevBtn.addEventListener('click', () => {
    currentSlideIndex--;
    if (currentSlideIndex < 0) {
        currentSlideIndex = slides.length - 1;
    }
    showSlide(currentSlideIndex);
});

nextBtn.addEventListener('click', () => {
    currentSlideIndex++;
    if (currentSlideIndex >= slides.length) {
        currentSlideIndex = 0;
    }
    showSlide(currentSlideIndex);
});

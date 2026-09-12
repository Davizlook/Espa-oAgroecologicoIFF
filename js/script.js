const firebaseConfig = window.firebaseConfig || null;

if (firebaseConfig) {
	import("https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js")
		.then(({ initializeApp }) => initializeApp(firebaseConfig))
		.then(() => console.info("Firebase conectado. A camada de dados está pronta."))
		.catch((error) => console.error("Não foi possível iniciar o Firebase:", error));
}

const menuToggle = document.querySelector(".menu-toggle");
const mainNav = document.querySelector(".main-nav");
const pageSections = document.querySelectorAll("[data-view]");
const navLinks = document.querySelectorAll(".nav-link");
const gallerySlides = document.querySelectorAll(".gallery-slide");
const galleryDots = document.querySelector(".gallery-dots");
let currentGallerySlide = 0;

function showPage(page) {
	const activePage = ["home", "catalogo", "agroecologia"].includes(page) ? page : "home";
	pageSections.forEach((section) => {
		section.hidden = section.dataset.view !== activePage;
	});
	navLinks.forEach((link) => link.classList.toggle("is-active", link.dataset.page === activePage));
	if (activePage !== "home") window.scrollTo({ top:0, behavior:"smooth" });
}

function handleRoute() {
	const route = window.location.hash.slice(1);
	showPage(["catalogo", "agroecologia"].includes(route) ? route : "home");
}

function updateGallery(nextSlide) {
	currentGallerySlide = (nextSlide + gallerySlides.length) % gallerySlides.length;
	gallerySlides.forEach((slide, index) => slide.classList.toggle("is-active", index === currentGallerySlide));
	galleryDots?.querySelectorAll("button").forEach((dot, index) => {
		dot.classList.toggle("is-active", index === currentGallerySlide);
		dot.setAttribute("aria-current", index === currentGallerySlide ? "true" : "false");
	});
}

gallerySlides.forEach((_, index) => {
	const dot = document.createElement("button");
	dot.type = "button";
	dot.ariaLabel = `Ver imagem ${index + 1}`;
	dot.addEventListener("click", () => updateGallery(index));
	galleryDots?.append(dot);
});

document.querySelector(".gallery-prev")?.addEventListener("click", () => updateGallery(currentGallerySlide - 1));
document.querySelector(".gallery-next")?.addEventListener("click", () => updateGallery(currentGallerySlide + 1));
updateGallery(0);

menuToggle.addEventListener("click", () => {
	const isOpen = mainNav.classList.toggle("is-open");
	menuToggle.setAttribute("aria-expanded", String(isOpen));
});

mainNav.addEventListener("click", () => {
	mainNav.classList.remove("is-open");
	menuToggle.setAttribute("aria-expanded", "false");
});

window.addEventListener("hashchange", handleRoute);
document.querySelector("#current-year").textContent = new Date().getFullYear();

const revealObserver = new IntersectionObserver((entries) => {
	entries.forEach((entry) => {
		if (entry.isIntersecting) {
			entry.target.classList.add("is-visible");
			revealObserver.unobserve(entry.target);
		}
	});
}, { threshold:0.12 });

document.querySelectorAll(".reveal").forEach((element) => revealObserver.observe(element));
handleRoute();

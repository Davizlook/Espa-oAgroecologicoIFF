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

function showPage(page) {
	const catalogIsVisible = page === "catalogo";
	pageSections.forEach((section) => {
		section.hidden = section.dataset.view === "catalogo" ? !catalogIsVisible : catalogIsVisible;
	});
	navLinks.forEach((link) => link.classList.toggle("is-active", link.dataset.page === page));
	if (catalogIsVisible) window.scrollTo({ top:0, behavior:"smooth" });
}

function handleRoute() {
	showPage(window.location.hash === "#catalogo" ? "catalogo" : "home");
}

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

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
	const currentPage = page === "catalogo" || page === "participantes" ? page : "home";
	pageSections.forEach((section) => {
		const shouldShow = section.dataset.view === currentPage;
		section.hidden = !shouldShow;
	});
	
	navLinks.forEach((link) => link.classList.toggle("is-active", link.dataset.page === currentPage));
	if (currentPage === "catalogo" || currentPage === "participantes") {
		window.scrollTo({ top:0, behavior:"smooth" });
	}
}

function handleRoute() {
	const routeMap = {
		"#catalogo": "catalogo"
	};

	showPage(routeMap[window.location.hash] || "home");
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

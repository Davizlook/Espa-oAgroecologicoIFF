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
const galleryStage = document.querySelector(".gallery-stage");
const lightbox = document.querySelector(".lightbox");
const lightboxImage = document.querySelector(".lightbox-image");
const lightboxClose = document.querySelector(".lightbox-close");
const galleryItems = [
	"felipe.jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.31 (1).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.31 (2).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.31.jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.32 (1).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.32.jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.33.jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.34 (1).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.34 (2).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.34 (3).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.34 (4).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.34.jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.35 (1).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.35 (2).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.35.jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.36 (1).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.36 (2).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.36 (3).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.36.jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.37 (1).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.37 (2).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.37.jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.38 (1).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.38.jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.39.jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.41.jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.42 (1).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.42 (2).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.42.jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.43.jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.44 (1).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.44.jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.45 (1).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.45 (2).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.45 (3).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.45 (4).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.45 (5).jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.45.jpeg",
	"WhatsApp Image 2026-09-02 at 18.56.46.jpeg",
	"WhatsApp Image 2026-09-03 at 11.55.56.jpeg",
	"WhatsApp Image 2026-09-03 at 11.56.09 (1).jpeg",
	"WhatsApp Image 2026-09-03 at 11.56.09.jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.50 (1).jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.50 (2).jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.50.jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.51 (1).jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.51 (2).jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.51 (3).jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.51.jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.52 (1).jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.52.jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.53 (1).jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.53 (2).jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.53 (3).jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.53 (4).jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.53.jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.53 (5).jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.54 (1).jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.54 (2).jpeg",
	"WhatsApp Image 2026-09-12 at 14.07.54.jpeg",
	"WhatsApp Image 2026-09-19 at 15.02.40.jpeg",
	"WhatsApp Image 2026-09-19 at 15.02.41 (1).jpeg",
	"WhatsApp Image 2026-09-19 at 15.02.41 (2).jpeg",
	"WhatsApp Image 2026-09-19 at 15.02.41 (3).jpeg",
	"WhatsApp Image 2026-09-19 at 15.02.41.jpeg",
	"WhatsApp Image 2026-09-19 at 15.02.42.jpeg",
	"WhatsApp Video 2026-09-19 at 15.03.02.mp4",
	"WhatsApp Video 2026-09-19 at 15.04.58.mp4"
];

if (galleryStage) {
	galleryStage.innerHTML = "";
	galleryItems.forEach((itemName, index) => {
		const isVideo = itemName.toLowerCase().endsWith(".mp4");
		const mediaPath = isVideo
			? `assets/videos/${encodeURIComponent(itemName).replace(/%2F/g, "/")}`
			: `assets/images/${encodeURIComponent(itemName).replace(/%2F/g, "/")}`;
		const wrapper = document.createElement("figure");
		wrapper.className = `gallery-item ${isVideo ? "gallery-video" : "gallery-photo"}`;

		if (index % 7 === 0) wrapper.classList.add("wide");
		if (index % 5 === 0) wrapper.classList.add("tall");
		if (index % 11 === 0) wrapper.classList.add("featured");

		if (isVideo) {
			const item = document.createElement("video");
			item.src = mediaPath;
			item.controls = true;
			item.playsInline = true;
			item.preload = "metadata";
			item.setAttribute("aria-label", `Vídeo ${index + 1} do espaço agroecológico`);
			item.title = `Vídeo ${index + 1} do espaço agroecológico`;
			const label = document.createElement("span");
			label.className = "gallery-video-label";
			label.textContent = "Vídeo";
			wrapper.append(item, label);
		} else {
			const item = document.createElement("img");
			item.src = mediaPath;
			item.alt = `Imagem ${index + 1} do espaço agroecológico`;
			item.loading = "eager";
			item.addEventListener("click", () => {
				lightboxImage.src = item.src;
				lightboxImage.alt = item.alt;
				lightbox.hidden = false;
				document.body.classList.add("lightbox-open");
				lightboxClose.focus();
			});
			wrapper.append(item);
		}

		galleryStage.append(wrapper);
	});
}

function closeLightbox() {
	lightbox.hidden = true;
	document.body.classList.remove("lightbox-open");
	lightboxImage.src = "";
}

lightboxClose?.addEventListener("click", closeLightbox);
lightbox?.addEventListener("click", (event) => {
	if (event.target === lightbox) closeLightbox();
});
document.addEventListener("keydown", (event) => {
	if (event.key === "Escape" && !lightbox.hidden) closeLightbox();
});

function showPage(page) {
	const activePage = ["home", "catalogo", "agroecologia", "galeria", "participantes"].includes(page) ? page : "home";
	pageSections.forEach((section) => {
		section.hidden = section.dataset.view !== activePage;
		if (section.dataset.view === activePage) {
			section.querySelectorAll(".reveal").forEach((element) => element.classList.add("is-visible"));
		}
	});
	navLinks.forEach((link) => link.classList.toggle("is-active", link.dataset.page === activePage));
	if (activePage !== "home") window.scrollTo({ top:0, behavior:"smooth" });
}

function handleRoute() {
	const route = window.location.hash.slice(1);
	showPage(["catalogo", "agroecologia", "galeria", "participantes"].includes(route) ? route : "home");
}

menuToggle?.addEventListener("click", () => {
	const isOpen = mainNav.classList.toggle("is-open");
	menuToggle.setAttribute("aria-expanded", String(isOpen));
});

mainNav?.addEventListener("click", () => {
	mainNav.classList.remove("is-open");
	menuToggle.setAttribute("aria-expanded", "false");
});

window.addEventListener("hashchange", handleRoute);
const currentYear = document.querySelector("#current-year");
if (currentYear) currentYear.textContent = new Date().getFullYear();

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

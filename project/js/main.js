// Χάρτης Leaflet 
const map = L.map("map").setView([38.04142764344666, 23.81671456203369], 16);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
  maxZoom: 19,
  attribution: "&copy; OpenStreetMap contributors"
}).addTo(map);

const icon = L.icon({
  iconUrl: "images/marker.png", 
  iconSize: [40, 40],
  iconAnchor: [20, 40],
  popupAnchor: [0, -32]
});

L.marker([38.04142764344666, 23.81671456203369], { icon: icon })
  .addTo(map)
  .bindPopup(`
    <div style="font-family: Lato; font-size: 15px;">
      <b style="font-size: 16px;">Μητροπολιτικό Κολλέγιο</b><br>
      Μαρούσι, Αθήνα<br><br>
      <a href="https://www.google.com/maps/dir/?api=1&destination=38.04142764344666,23.81671456203369"
         target="_blank"
         style="color:#b71c1c; font-weight:600; text-decoration:none;">
        ➤ Οδηγίες με Google Maps
      </a>
    </div>
  `)
  .openPopup();

map.zoomControl.setPosition("bottomright");

//animation sto scroll
const fadeBlocks = document.querySelectorAll(".fade-left, .fade-right");

const fadeObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add("visible");
    }
  });
}, { threshold: 0.2 });

fadeBlocks.forEach(block => fadeObserver.observe(block));


// back to top button
const backToTop = document.getElementById("backToTop");

window.addEventListener("scroll", () => {
  backToTop.classList.toggle("show", window.scrollY > 200);
});

backToTop.addEventListener("click", () => {
  window.scrollTo({
    top: 0,
    behavior: "smooth"
  });
});

// smooth scroll 
const contactLink = document.querySelector('a[href="#map-section"]');

if (contactLink) {
  contactLink.addEventListener("click", (e) => {
    e.preventDefault();
    document.querySelector("#map-section").scrollIntoView({
      behavior: "smooth"
    });
  });
}


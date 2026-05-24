let gallerySession = null;

document.addEventListener("DOMContentLoaded", async () => {
  try {
    gallerySession = await window.movieApp.refreshSessionUi();
    await renderGallery();
    await renderMyRatings();
    updateGallerySessionBlocks();
  } catch (error) {
    window.movieApp.showNotification(error.message, "error");
  }
});

async function renderGallery() {
  const payload = await window.movieApp.requestJson("/api/images.php");
  const gallery = document.getElementById("gallery");
  const emptyGallery = document.getElementById("emptyGallery");

  gallery.innerHTML = "";

  if (!payload.images.length) {
    emptyGallery.hidden = false;
    return;
  }

  emptyGallery.hidden = true;

  payload.images.forEach((image) => {
    const article = document.createElement("article");
    article.className = "gallery-item";

    article.innerHTML = `
      <figure>
        <img src="${window.movieApp.appUrl(window.movieApp.escapeHtml(image.file))}" alt="${window.movieApp.escapeHtml(image.title)}">
        <figcaption>
          <h3>${window.movieApp.escapeHtml(image.title)}</h3>
          <p>${window.movieApp.escapeHtml(image.description || "Bez opisa.")}</p>
        </figcaption>
      </figure>
      <div class="rating-summary">
        <span>Prosjek: <strong>${image.averageRating === null ? "Nema ocjena" : image.averageRating.toFixed(2)}</strong></span>
        <span>Broj ocjena: <strong>${image.ratingCount}</strong></span>
      </div>
      <form class="rating-form" data-image-id="${image.id}">
        <label>Moja ocjena</label>
        <div class="star-row">
          ${createStarInputs(image.id, image.userRating)}
        </div>
        <button type="submit">Spremi ocjenu</button>
      </form>
    `;

    gallery.appendChild(article);
  });

  gallery.querySelectorAll(".rating-form").forEach((form) => {
    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      const checkedInput = form.querySelector("input:checked");

      if (!checkedInput) {
        window.movieApp.showNotification("Odaberite ocjenu od 1 do 5.", "error");
        return;
      }

      try {
        const payload = await window.movieApp.requestJson(
          "/api/rate_image.php",
          {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              imageId: Number(form.dataset.imageId),
              rating: Number(checkedInput.value),
            }),
          }
        );

        window.movieApp.showNotification(payload.message, "success");
        gallerySession = await window.movieApp.refreshSessionUi();
        await renderGallery();
        await renderMyRatings();
        updateGallerySessionBlocks();
      } catch (error) {
        window.movieApp.showNotification(error.message, "error");
      }
    });
  });

  if (!gallerySession || !gallerySession.authenticated) {
    gallery.querySelectorAll(".rating-form").forEach((form) => {
      const button = form.querySelector("button");
      const inputs = form.querySelectorAll("input");
      button.disabled = true;
      inputs.forEach((input) => {
        input.disabled = true;
      });
    });
  }
}

async function renderMyRatings() {
  const tableBody = document.getElementById("myRatingsBody");
  const emptyState = document.getElementById("emptyRatings");

  if (!gallerySession || !gallerySession.authenticated) {
    tableBody.innerHTML = "";
    emptyState.hidden = false;
    emptyState.textContent = "Prijavite se kako biste vidjeli vlastite ocjene.";
    return;
  }

  const payload = await window.movieApp.requestJson("/api/my_ratings.php");
  tableBody.innerHTML = "";

  if (!payload.ratings.length) {
    emptyState.hidden = false;
    emptyState.textContent = "Jos niste ocijenili nijednu sliku.";
    return;
  }

  emptyState.hidden = true;

  payload.ratings.forEach((entry) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${window.movieApp.escapeHtml(entry.image.title)}</td>
      <td>${entry.rating}/5</td>
      <td>${new Date(entry.updatedAt).toLocaleString("hr-HR")}</td>
    `;
    tableBody.appendChild(row);
  });
}

function updateGallerySessionBlocks() {
  const hint = document.getElementById("ratingAccessHint");
  if (!hint) {
    return;
  }

  hint.textContent =
    gallerySession && gallerySession.authenticated
      ? "Ocjene se trajno spremaju na server i vezane su uz vas korisnicki racun."
      : "Za ocjenjivanje slika potrebna je prijava.";
}

function createStarInputs(imageId, selectedRating) {
  let html = "";
  for (let rating = 5; rating >= 1; rating -= 1) {
    const inputId = `rating-${imageId}-${rating}`;
    html += `
      <input type="radio" id="${inputId}" name="rating-${imageId}" value="${rating}" ${
        Number(selectedRating) === rating ? "checked" : ""
      }>
      <label for="${inputId}" title="${rating} zvjezdica">&#9733;</label>
    `;
  }
  return html;
}

let dashboardSession = null;

document.addEventListener("DOMContentLoaded", async () => {
  bindMovieForm();
  bindImageForm();

  try {
    dashboardSession = await window.movieApp.refreshSessionUi();
    guardAdminPage();
    if (dashboardSession && dashboardSession.authenticated && dashboardSession.user.role === "admin") {
      await Promise.all([renderMovies(), renderImages()]);
    }
  } catch (error) {
    window.movieApp.showNotification(error.message, "error");
  }
});

function guardAdminPage() {
  const adminPanel = document.getElementById("adminPanel");
  const deniedPanel = document.getElementById("accessDenied");

  const isAdmin = dashboardSession && dashboardSession.authenticated && dashboardSession.user.role === "admin";

  adminPanel.hidden = !isAdmin;
  deniedPanel.hidden = isAdmin;
}

function bindMovieForm() {
  const form = document.getElementById("movieForm");
  form.addEventListener("submit", async (event) => {
    event.preventDefault();

    const movieId = form.dataset.editingId;
    const payload = Object.fromEntries(new FormData(form).entries());
    const method = movieId ? "PUT" : "POST";
    const endpoint = movieId ? `/api/movies.php?id=${movieId}` : "/api/movies.php";

    try {
      const response = await window.movieApp.requestJson(endpoint, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      window.movieApp.showNotification(response.message, "success");
      form.reset();
      delete form.dataset.editingId;
      document.getElementById("movieSubmitLabel").textContent = "Dodaj film";
      await Promise.all([renderMovies(), window.movieApp.refreshSessionUi()]);
    } catch (error) {
      window.movieApp.showNotification(error.message, "error");
    }
  });
}

function bindImageForm() {
  const form = document.getElementById("imageForm");
  form.addEventListener("submit", async (event) => {
    event.preventDefault();

    const fileInput = document.getElementById("imageFile");
    const file = fileInput.files[0];

    if (!file) {
      window.movieApp.showNotification("Odaberite JPEG ili PNG datoteku.", "error");
      return;
    }

    if (!["image/jpeg", "image/png"].includes(file.type)) {
      window.movieApp.showNotification("Dozvoljeni su samo JPEG i PNG formati.", "error");
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      window.movieApp.showNotification("Datoteka mora biti manja od 5 MB.", "error");
      return;
    }

    try {
      const dataUrl = await readFileAsDataUrl(file);
      const response = await window.movieApp.requestJson("/api/admin_images.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          title: document.getElementById("imageTitle").value,
          description: document.getElementById("imageDescription").value,
          dataUrl,
        }),
      });

      window.movieApp.showNotification(response.message, "success");
      form.reset();
      await renderImages();
    } catch (error) {
      window.movieApp.showNotification(error.message, "error");
    }
  });
}

async function renderMovies() {
  const payload = await window.movieApp.requestJson("/api/movies.php");
  const tbody = document.getElementById("movieAdminBody");
  tbody.innerHTML = "";

  payload.movies.forEach((movie) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${window.movieApp.escapeHtml(movie.Naslov)}</td>
      <td>${movie.Godina}</td>
      <td>${movie.Trajanje_min}</td>
      <td>${movie.Ocjena.toFixed(1)}</td>
      <td class="actions-cell">
        <button type="button" data-edit-movie="${movie.id}">Edit</button>
        <button type="button" data-delete-movie="${movie.id}" class="danger-btn">Delete</button>
      </td>
    `;
    tbody.appendChild(row);
  });

  tbody.querySelectorAll("[data-edit-movie]").forEach((button) => {
    button.addEventListener("click", async () => {
      const payload = await window.movieApp.requestJson("/api/movies.php");
      const movie = payload.movies.find((entry) => entry.id === Number(button.dataset.editMovie));

      if (!movie) {
        return;
      }

      const form = document.getElementById("movieForm");
      form.dataset.editingId = String(movie.id);
      form.Naslov.value = movie.Naslov;
      form.Zanr.value = movie.Zanr;
      form.Godina.value = movie.Godina;
      form.Trajanje_min.value = movie.Trajanje_min;
      form.Ocjena.value = movie.Ocjena;
      form.Rezisery.value = movie.Rezisery;
      form.Zemlja_porijekla.value = movie.Zemlja_porijekla;
      document.getElementById("movieSubmitLabel").textContent = "Azuriraj film";
    });
  });

  tbody.querySelectorAll("[data-delete-movie]").forEach((button) => {
    button.addEventListener("click", async () => {
      try {
        const response = await window.movieApp.requestJson(
          `/api/movies.php?id=${button.dataset.deleteMovie}`,
          { method: "DELETE" }
        );
        window.movieApp.showNotification(response.message, "success");
        await renderMovies();
      } catch (error) {
        window.movieApp.showNotification(error.message, "error");
      }
    });
  });
}

async function renderImages() {
  const payload = await window.movieApp.requestJson("/api/images.php");
  const tbody = document.getElementById("imageAdminBody");
  tbody.innerHTML = "";

  payload.images.forEach((image) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${window.movieApp.escapeHtml(image.title)}</td>
      <td>${window.movieApp.escapeHtml(image.source)}</td>
      <td>${image.averageRating === null ? "Nema" : image.averageRating.toFixed(2)}</td>
      <td>${image.ratingCount}</td>
      <td class="actions-cell">
        <button type="button" data-delete-image="${image.id}" class="danger-btn">Delete</button>
      </td>
    `;
    tbody.appendChild(row);
  });

  tbody.querySelectorAll("[data-delete-image]").forEach((button) => {
    button.addEventListener("click", async () => {
      try {
        const response = await window.movieApp.requestJson(
          `/api/admin_images.php?id=${button.dataset.deleteImage}`,
          { method: "DELETE" }
        );
        window.movieApp.showNotification(response.message, "success");
        await renderImages();
      } catch (error) {
        window.movieApp.showNotification(error.message, "error");
      }
    });
  });
}

function readFileAsDataUrl(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = () => reject(new Error("Datoteku nije moguce procitati."));
    reader.readAsDataURL(file);
  });
}

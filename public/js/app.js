let movies = [];
let currentSession = null;
const knownGenres = new Set();

document.addEventListener("DOMContentLoaded", async () => {
  bindAuthForms();
  bindFilters();

  try {
    currentSession = await window.movieApp.refreshSessionUi();
    await loadMovies();
    updateHomeSessionCard();
  } catch (error) {
    window.movieApp.showNotification(error.message, "error");
  }
});

function bindAuthForms() {
  const loginForm = document.getElementById("loginForm");
  const registerForm = document.getElementById("registerForm");

  if (loginForm) {
    loginForm.addEventListener("submit", async (event) => {
      event.preventDefault();

      const formData = new FormData(loginForm);

      try {
        const payload = await window.movieApp.requestJson("/api/login.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            username: formData.get("username"),
            password: formData.get("password"),
          }),
        });

        loginForm.reset();
        currentSession = await window.movieApp.refreshSessionUi();
        updateHomeSessionCard();
        await loadMovies();
        window.movieApp.showNotification(payload.message, "success");
      } catch (error) {
        window.movieApp.showNotification(error.message, "error");
      }
    });
  }

  if (registerForm) {
    registerForm.addEventListener("submit", async (event) => {
      event.preventDefault();

      const formData = new FormData(registerForm);

      try {
        const payload = await window.movieApp.requestJson("/api/register.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            username: formData.get("username"),
            password: formData.get("password"),
            confirmPassword: formData.get("confirmPassword"),
          }),
        });

        registerForm.reset();
        currentSession = await window.movieApp.refreshSessionUi();
        updateHomeSessionCard();
        await loadMovies();
        window.movieApp.showNotification(payload.message, "success");
      } catch (error) {
        window.movieApp.showNotification(error.message, "error");
      }
    });
  }
}

function bindFilters() {
  document.getElementById("applyFilters").addEventListener("click", loadMovies);
  document.getElementById("sortBy").addEventListener("change", loadMovies);
}

async function loadMovies() {
  const query = new URLSearchParams({
    genre: document.getElementById("genreFilter").value,
    yearFrom: document.getElementById("yearFrom").value,
    yearTo: document.getElementById("yearTo").value,
    country: document.getElementById("countryFilter").value,
    ratingFrom: document.getElementById("ratingFrom").value,
    ratingTo: document.getElementById("ratingTo").value,
    sortBy: document.getElementById("sortBy").value,
  });

  const payload = await window.movieApp.requestJson(`/api/movies.php?${query.toString()}`);
  movies = payload.movies;

  populateGenreFilter(movies);
  renderTable(movies);
}

function populateGenreFilter(movieList) {
  const genreSelect = document.getElementById("genreFilter");
  const selectedGenre = genreSelect.value;

  movieList.forEach((movie) => {
    movie.Zanr.split(",").forEach((genre) => {
      knownGenres.add(genre.trim());
    });
  });

  genreSelect.innerHTML = '<option value="">All Genres</option>';
  [...knownGenres].sort().forEach((genre) => {
    const option = document.createElement("option");
    option.value = genre;
    option.textContent = genre;
    option.selected = genre === selectedGenre;
    genreSelect.appendChild(option);
  });
}

function renderTable(movieList) {
  const tbody = document.getElementById("movieTableBody");
  tbody.innerHTML = "";

  movieList.forEach((movie, index) => {
    const row = document.createElement("tr");
    const isDisabled = (movie.inLibrary || movie.isWatched) ? "disabled" : "";

    row.innerHTML = `
      <td>${index + 1}</td>
      <td>${window.movieApp.escapeHtml(movie.Naslov)}</td>
      <td>${movie.Godina}</td>
      <td>${window.movieApp.escapeHtml(movie.Zanr)}</td>
      <td>${movie.Trajanje_min}</td>
      <td>${window.movieApp.escapeHtml(movie.Zemlja_porijekla)}</td>
      <td>${movie.Ocjena.toFixed(1)}</td>
      <td>
        <button class="add-btn" ${isDisabled} data-movie-id="${movie.id}">
          ${
            movie.isWatched
              ? "Watched"
              : movie.inLibrary
              ? "Added"
              : "Add"
          }
        </button>
      </td>
    `;
    tbody.appendChild(row);
  });

  tbody.querySelectorAll("[data-movie-id]").forEach((button) => {
    button.addEventListener("click", async () => {
      const movieId = Number(button.dataset.movieId);
      await addToLibrary(movieId);
    });
  });
}

async function addToLibrary(movieId) {
  try {
    const payload = await window.movieApp.requestJson("/api/library.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ movieId }),
    });

    currentSession = await window.movieApp.refreshSessionUi();
    updateHomeSessionCard();
    await loadMovies();

    window.movieApp.showNotification(payload.warning || payload.message, payload.warning ? "error" : "success");
  } catch (error) {
    window.movieApp.showNotification(error.message, "error");
  }
}

function updateHomeSessionCard() {
  const summary = document.getElementById("sessionSummary");
  const guestHint = document.getElementById("guestHint");
  const userActions = document.getElementById("userActions");

  if (!summary || !guestHint || !userActions) {
    return;
  }

  if (currentSession && currentSession.authenticated) {
    summary.innerHTML = `
      <strong>${window.movieApp.escapeHtml(currentSession.user.username)}</strong>
      koristi ulogu <strong>${window.movieApp.escapeHtml(currentSession.user.role)}</strong>.
      U osobnoj videoteci trenutno je <strong>${currentSession.libraryCount}</strong> filmova.
    `;
    guestHint.hidden = true;
    userActions.hidden = false;
  } else {
    summary.textContent = "Prijavite se ili kreirajte korisnicki racun za spremanje filmova i ocjenjivanje slika.";
    guestHint.hidden = false;
    userActions.hidden = true;
  }
}

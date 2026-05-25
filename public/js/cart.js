document.addEventListener("DOMContentLoaded", async () => {
  try {
    const session = await window.movieApp.refreshSessionUi();
    if (!session.authenticated) {
      renderGuestState();
      return;
    }

    await renderLibrary();
  } catch (error) {
    window.movieApp.showNotification(error.message, "error");
  }
});

async function renderLibrary() {
  const payload = await window.movieApp.requestJson("/api/library.php");
  const libraryBody = document.getElementById("cartBody");
  const watchedBody = document.getElementById("watchedBody");
  const emptyState = document.getElementById("emptyLibrary");
  const emptyWatched = document.getElementById("emptyWatched");
  const confirmButton = document.getElementById("confirm");

  libraryBody.innerHTML = "";
  watchedBody.innerHTML = "";

  if (!payload.movies.length) {
    emptyState.hidden = false;
    confirmButton.disabled = true;
  } else {
    emptyState.hidden = true;
    confirmButton.disabled = false;

    payload.movies.forEach((movie) => {
      const row = document.createElement("tr");
      row.innerHTML = `
        <td>${window.movieApp.escapeHtml(movie.Naslov)}</td>
        <td>${movie.Godina}</td>
        <td>${movie.Ocjena.toFixed(1)}</td>
        <td><button data-remove-id="${movie.id}">Remove</button></td>
      `;
      libraryBody.appendChild(row);
    });

    libraryBody.querySelectorAll("[data-remove-id]").forEach((button) => {
      button.addEventListener("click", async () => {
        try {
          const response = await window.movieApp.requestJson(`/api/library.php?movieId=${button.dataset.removeId}`, {
            method: "DELETE",
          });
          window.movieApp.showNotification(response.message, "success");
          await window.movieApp.refreshSessionUi();
          await renderLibrary();
        } catch (error) {
          window.movieApp.showNotification(error.message, "error");
        }
      });
    });
  }

  if (!payload.watchedMovies.length) {
    emptyWatched.hidden = false;
    return;
  }

  emptyWatched.hidden = true;

  payload.watchedMovies.forEach((movie) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${window.movieApp.escapeHtml(movie.Naslov)}</td>
      <td>${movie.Godina}</td>
      <td>${movie.Ocjena.toFixed(1)}</td>
      <td>${formatWatchedAt(movie.watchedAt)}</td>
    `;
    watchedBody.appendChild(row);
  });
}

function renderGuestState() {
  document.getElementById("emptyLibrary").hidden = false;
  document.getElementById("emptyLibrary").textContent =
    "Prijavite se kako biste koristili osobnu videoteka listu.";
  document.getElementById("emptyWatched").hidden = false;
  document.getElementById("emptyWatched").textContent =
    "Prijavite se kako biste vidjeli pogledane filmove.";
  document.getElementById("confirm").disabled = true;
}

document.getElementById("confirm").addEventListener("click", async () => {
  try {
    const payload = await window.movieApp.requestJson("/api/library.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "confirmMarathon" }),
    });

    window.movieApp.showNotification(
      `Vikend maraton je potvrden s ${payload.count} filmova.`,
      "success"
    );

    await window.movieApp.refreshSessionUi();
    await renderLibrary();

  } catch (error) {
    window.movieApp.showNotification(error.message, "error");
  }
});

function formatWatchedAt(value) {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return window.movieApp.escapeHtml(value);
  }

  return new Intl.DateTimeFormat("hr-HR", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date);
}

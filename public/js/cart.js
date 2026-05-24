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
  const tbody = document.getElementById("cartBody");
  const emptyState = document.getElementById("emptyLibrary");
  const confirmButton = document.getElementById("confirm");

  tbody.innerHTML = "";

  if (!payload.movies.length) {
    emptyState.hidden = false;
    confirmButton.disabled = true;
    return;
  }

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
    tbody.appendChild(row);
  });

  tbody.querySelectorAll("[data-remove-id]").forEach((button) => {
    button.addEventListener("click", async () => {
      try {
        const payload = await window.movieApp.requestJson(`/api/library.php?movieId=${button.dataset.removeId}`, {
          method: "DELETE",
        });
        window.movieApp.showNotification(payload.message, "success");
        await window.movieApp.refreshSessionUi();
        await renderLibrary();
      } catch (error) {
        window.movieApp.showNotification(error.message, "error");
      }
    });
  });
} 

function renderGuestState() {
  document.getElementById("emptyLibrary").hidden = false;
  document.getElementById("emptyLibrary").textContent =
    "Prijavite se kako biste koristili osobnu videoteka listu.";
  document.getElementById("confirm").disabled = true;
}

document.getElementById("confirm").addEventListener("click", async () => {
  try {

    const payload = await window.movieApp.requestJson("/api/library.php", {
      method: "PUT",
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

document.addEventListener("DOMContentLoaded", async () => {
  try {
    await window.movieApp.refreshSessionUi();
    const payload = await window.movieApp.requestJson("/api/stats.php");
    createGenreChart(payload.genreDistribution);
    createAverageRatingChart(payload.averageRatings);
  } catch (error) {
    window.movieApp.showNotification(error.message, "error");
  }
});

function createGenreChart(genreDistribution) {
  new Chart(document.getElementById("genreChart"), {
    type: "doughnut",
    data: {
      labels: genreDistribution.map((item) => item.genre),
      datasets: [
        {
          data: genreDistribution.map((item) => item.count),
        },
      ],
    },
    options: {
      plugins: {
        legend: { display: true },
      },
    },
  });
}

function createAverageRatingChart(averageRatings) {
  new Chart(document.getElementById("ratingChart"), {
    type: "bar",
    data: {
      labels: averageRatings.map((item) => item.genre),
      datasets: [
        {
          label: "Average Rating",
          data: averageRatings.map((item) => item.average),
        },
      ],
    },
    options: {
      scales: {
        y: {
          beginAtZero: true,
          max: 10,
        },
      },
      plugins: {
        legend: { display: false },
      },
    },
  });
}

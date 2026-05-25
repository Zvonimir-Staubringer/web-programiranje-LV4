(function () {
  function appUrl(path) {
    if (/^(?:https?:)?\/\//.test(path)) {
      return path;
    }

    const base = window.APP_BASE || "";
    const normalizedPath = String(path || "").replace(/^\/+/, "");
    return normalizedPath ? `${base}/${normalizedPath}` : base;
  }

  async function requestJson(url, options) {
    const response = await fetch(appUrl(url), {
      cache: "no-store",
      ...options,
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      const detailSuffix = payload.details ? ` (${payload.details})` : "";
      throw new Error((payload.message || "Dogodila se pogreska na serveru.") + detailSuffix);
    }

    return payload;
  }

  function escapeHtml(value) {
    return String(value)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#39;");
  }

  function showNotification(message, type) {
    const notif = document.getElementById("notification");
    if (!notif) {
      return;
    }

    notif.textContent = message;
    notif.classList.remove("is-error", "is-success", "show");
    notif.classList.add(type === "error" ? "is-error" : "is-success");
    notif.classList.add("show");

    window.clearTimeout(showNotification.timeoutId);
    showNotification.timeoutId = window.setTimeout(() => {
      notif.classList.remove("show");
    }, 3200);
  }

  async function getSession() {
    return requestJson("api/session.php");
  }

  async function refreshSessionUi() {
    const session = await getSession();

    const cartCount = document.getElementById("cartCount");
    if (cartCount) {
      cartCount.textContent = session.libraryCount || 0;
    }

    const navUser = document.getElementById("navUser");
    if (navUser) {
      navUser.textContent = session.authenticated
        ? `${session.user.username} (${session.user.role})`
        : "Guest";
    }

    const logoutButton = document.getElementById("logoutButton");
    if (logoutButton) {
      logoutButton.hidden = !session.authenticated;
    }

    const dashboardNavItem = document.getElementById("dashboardNavItem");
    if (dashboardNavItem) {
      dashboardNavItem.hidden = !(session.authenticated && session.user.role === "admin");
    }

    const authOnlyBlocks = document.querySelectorAll("[data-auth-required]");
    authOnlyBlocks.forEach((block) => {
      block.hidden = !session.authenticated;
    });

    const guestOnlyBlocks = document.querySelectorAll("[data-guest-only]");
    guestOnlyBlocks.forEach((block) => {
      block.hidden = session.authenticated;
    });

    const sessionBlocks = document.querySelectorAll("[data-session-summary]");
    sessionBlocks.forEach((block) => {
      block.innerHTML = session.authenticated
        ? `Prijavljeni ste kao <strong>${escapeHtml(session.user.username)}</strong>.`
        : "Niste prijavljeni.";
    });

    return session;
  }

  async function logout() {
    const payload = await requestJson("api/logout.php", { method: "POST" });
    showNotification(payload.message, "success");
    return refreshSessionUi();
  }

  function wireLogoutButton() {
    const logoutButton = document.getElementById("logoutButton");
    if (!logoutButton || logoutButton.dataset.bound === "true") {
      return;
    }

    logoutButton.dataset.bound = "true";
    logoutButton.addEventListener("click", async () => {
      try {
        await logout();
        if (window.location.pathname.endsWith("/dashboard.php")) {
          window.location.href = appUrl("index.php");
          return;
        }
        window.location.reload();
      } catch (error) {
        showNotification(error.message, "error");
      }
    });
  }

  window.movieApp = {
    escapeHtml,
    appUrl,
    getSession,
    logout,
    refreshSessionUi,
    requestJson,
    showNotification,
  };

  document.addEventListener("DOMContentLoaded", () => {
    wireLogoutButton();
    refreshSessionUi().catch((error) => {
      showNotification(error.message, "error");
    });
  });
})();

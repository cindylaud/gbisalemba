(function () {
  var root = document.getElementById("heroCountdown");
  if (!root) return;

  var daysEl = document.getElementById("heroCountdownDays");
  var hoursEl = document.getElementById("heroCountdownHours");
  var minutesEl = document.getElementById("heroCountdownMinutes");
  var secondsEl = document.getElementById("heroCountdownSeconds");
  var labelEl = document.getElementById("heroCountdownLabel");
  var serviceEl = document.getElementById("heroServiceLabel");
  var serverClockEl = document.getElementById("heroServerClock");
  var valuesEl = document.getElementById("heroCountdownValues");
  var liveActionsEl = document.getElementById("heroLiveActions");
  var livePrimaryEl = document.getElementById("heroLivePrimary");
  var liveSecondaryEl = document.getElementById("heroLiveSecondary");

  var liveWindowMinutes = Number(root.getAttribute("data-live-window") || 120);
  var serviceSlots = [
    { hour: 8, minute: 0, title: "Ibadah Raya 08:00 WIB" },
    { hour: 10, minute: 30, title: "Ibadah Raya 10:30 WIB" },
    { hour: 17, minute: 0, title: "Ibadah Raya 17:00 WIB" },
  ];
  var serverNowAtLoad = Number(
    root.getAttribute("data-server-now") || Date.now(),
  );
  var clientNowAtLoad = Date.now();

  var serverClockFormatter = new Intl.DateTimeFormat("id-ID", {
    timeZone: "Asia/Jakarta",
    weekday: "long",
    day: "2-digit",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false,
  });

  var partsFormatter = new Intl.DateTimeFormat("en-US", {
    timeZone: "Asia/Jakarta",
    weekday: "short",
    year: "numeric",
    month: "numeric",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false,
  });

  function getServerNowMs() {
    return serverNowAtLoad + (Date.now() - clientNowAtLoad);
  }

  function pad(value) {
    return String(value).padStart(2, "0");
  }

  function isMobileView() {
    return window.matchMedia("(max-width: 768px)").matches;
  }

  function serviceTitleForView(title) {
    if (!title) return "";
    return isMobileView() ? title.replace("Ibadah Raya ", "") : title;
  }

  function formatServerClock(timestamp) {
    var parts = {};
    serverClockFormatter
      .formatToParts(new Date(timestamp))
      .forEach(function (part) {
        if (part.type !== "literal") {
          parts[part.type] = part.value;
        }
      });

    return (
      parts.weekday.charAt(0).toUpperCase() +
      parts.weekday.slice(1) +
      ", " +
      parts.day +
      " " +
      parts.month +
      " " +
      parts.year +
      " | " +
      parts.hour +
      ":" +
      parts.minute +
      ":" +
      parts.second +
      " WIB"
    );
  }

  function getWibParts(timestamp) {
    var parts = {};
    partsFormatter.formatToParts(new Date(timestamp)).forEach(function (part) {
      if (part.type !== "literal") {
        parts[part.type] = part.value;
      }
    });

    var weekdayMap = { Sun: 0, Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6 };
    return {
      weekdayIndex: weekdayMap[parts.weekday] || 0,
      year: parseInt(parts.year, 10),
      month: parseInt(parts.month, 10),
      day: parseInt(parts.day, 10),
    };
  }

  function addDays(dateParts, dayOffset) {
    var date = new Date(
      Date.UTC(dateParts.year, dateParts.month - 1, dateParts.day),
    );
    date.setUTCDate(date.getUTCDate() + dayOffset);
    return {
      year: date.getUTCFullYear(),
      month: date.getUTCMonth() + 1,
      day: date.getUTCDate(),
    };
  }

  function buildSlotTimestamp(dateParts, hour, minute) {
    return Date.UTC(
      dateParts.year,
      dateParts.month - 1,
      dateParts.day,
      hour - 7,
      minute,
      0,
      0,
    );
  }

  function getSlotsForDate(dateParts, dayOffset) {
    var shiftedDate = addDays(dateParts, dayOffset);
    return serviceSlots.map(function (slot) {
      return {
        title: slot.title,
        shiftedStartTs: buildSlotTimestamp(shiftedDate, slot.hour, slot.minute),
      };
    });
  }

  function renderCountdown(diffMs) {
    var totalSeconds = Math.max(0, Math.floor(diffMs / 1000));
    var days = Math.floor(totalSeconds / 86400);
    var hours = Math.floor((totalSeconds % 86400) / 3600);
    var minutes = Math.floor((totalSeconds % 3600) / 60);
    var seconds = totalSeconds % 60;

    if (daysEl) daysEl.textContent = pad(days);
    if (hoursEl) hoursEl.textContent = pad(hours);
    if (minutesEl) minutesEl.textContent = pad(minutes);
    if (secondsEl) secondsEl.textContent = pad(seconds);
  }

  function resolveCurrentState() {
    var shiftedNow = getServerNowMs();
    var nowParts = getWibParts(shiftedNow);
    var liveWindowMs = liveWindowMinutes * 60 * 1000;
    var offsets =
      nowParts.weekdayIndex === 0
        ? [0, 7]
        : [7 - nowParts.weekdayIndex, 14 - nowParts.weekdayIndex];
    var slots = [];

    offsets.forEach(function (offset) {
      slots = slots.concat(getSlotsForDate(nowParts, offset));
    });

    var currentLive = null;
    var nextSlot = null;

    slots.forEach(function (slot) {
      if (
        shiftedNow >= slot.shiftedStartTs &&
        shiftedNow < slot.shiftedStartTs + liveWindowMs
      ) {
        if (!currentLive || slot.shiftedStartTs > currentLive.shiftedStartTs) {
          currentLive = slot;
        }
      }

      if (slot.shiftedStartTs > shiftedNow) {
        if (!nextSlot || slot.shiftedStartTs < nextSlot.shiftedStartTs) {
          nextSlot = slot;
        }
      }
    });

    return {
      shiftedNow: shiftedNow,
      nowParts: nowParts,
      isLive: !!currentLive,
      liveSlot: currentLive,
      nextSlot: nextSlot,
    };
  }

  function updateHeroCountdown() {
    var state = resolveCurrentState();

    if (serverClockEl) {
      serverClockEl.textContent =
        "Server sekarang: " + formatServerClock(state.shiftedNow);
    }

    if (state.isLive) {
      if (!window.fireworksLaunched) {
        window.fireworksLaunched = true;

        if (typeof window.launchFireworks === "function") {
          window.launchFireworks();
        }
      }
      root.classList.add("is-live");
      if (labelEl)
        labelEl.textContent = isMobileView()
          ? "Live Sekarang"
          : "Ibadah Sedang Berlangsung";
      if (serviceEl)
        serviceEl.textContent = state.liveSlot
          ? serviceTitleForView(state.liveSlot.title)
          : "Ibadah Sedang Berlangsung";
      if (valuesEl) valuesEl.style.display = "none";
      if (liveActionsEl) liveActionsEl.hidden = false;
      if (livePrimaryEl) livePrimaryEl.textContent = "Gabung Online";
      if (liveSecondaryEl) liveSecondaryEl.textContent = "Lokasi Gereja";

      return;
    }

    root.classList.remove("is-live");
    window.fireworksLaunched = false;
    if (labelEl) labelEl.textContent = "Ibadah Berikutnya Dimulai Dalam";
    if (valuesEl) valuesEl.style.display = "";
    if (liveActionsEl) liveActionsEl.hidden = false;
    if (livePrimaryEl) livePrimaryEl.textContent = "Ke YouTube";
    if (liveSecondaryEl)
      liveSecondaryEl.textContent = isMobileView() ? "Lokasi" : "Lokasi Gereja";

    if (!state.nextSlot) {
      renderCountdown(0);
      if (serviceEl) serviceEl.textContent = "Jadwal ibadah belum tersedia";
      return;
    }

    renderCountdown(state.nextSlot.shiftedStartTs - state.shiftedNow);

    var currentDayStart = Date.UTC(
      state.nowParts.year,
      state.nowParts.month - 1,
      state.nowParts.day,
      0,
      0,
      0,
      0,
    );
    var nextSlotParts = getWibParts(state.nextSlot.shiftedStartTs);
    var nextDayStart = Date.UTC(
      nextSlotParts.year,
      nextSlotParts.month - 1,
      nextSlotParts.day,
      0,
      0,
      0,
      0,
    );
    var dayDiff = Math.max(
      0,
      Math.round((nextDayStart - currentDayStart) / (24 * 60 * 60 * 1000)),
    );
    var dayText =
      dayDiff === 0
        ? "Hari ini"
        : dayDiff === 1
          ? "1 hari lagi"
          : dayDiff + " hari lagi";

    if (serviceEl)
      serviceEl.textContent =
        dayText + " • " + serviceTitleForView(state.nextSlot.title);
  }

  updateHeroCountdown();
  setInterval(updateHeroCountdown, 1000);
})();

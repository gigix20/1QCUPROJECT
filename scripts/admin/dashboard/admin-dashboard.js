// API ROUTES
var ASSETS_API = "/1QCUPROJECT/backend/routes/assets_route.php";
var BORROW_API = "/1QCUPROJECT/backend/routes/borrows_route.php";
var MAINT_API = "/1QCUPROJECT/backend/routes/maintenance_route.php";
var USERS_API = "/1QCUPROJECT/backend/routes/users_route.php";
var REPORTS_API = "/1QCUPROJECT/backend/routes/reports_route.php";

// DATA STORES
var dashAssets = [];
var dashBorrows = [];
var dashMaintenance = [];

// ─── UTILITIES ────────────────────────────────────────────────────────────────

function showToast(msg) {
  var toastEl = document.getElementById("toast");
  if (!toastEl) return;
  toastEl.textContent = msg;
  toastEl.classList.add("show");
  setTimeout(function () {
    toastEl.classList.remove("show");
  }, 3000);
}

function openModal(id) {
  var el = document.getElementById(id);
  if (el) el.classList.add("active");
}

function closeModal(id) {
  var el = document.getElementById(id);
  if (el) el.classList.remove("active");
}

function formatDate(dateStr) {
  if (!dateStr) return "—";
  var d = new Date(dateStr);
  return d.toLocaleDateString("en-PH", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

function set(id, v) {
  var el = document.getElementById(id);
  if (el) el.textContent = v;
}

// ─── DEPT COLORS ──────────────────────────────────────────────────────────────

var DEPT_COLORS = {
  CICS: "#1d4ed8",
  COENG: "#b45309",
  COED: "#15803d",
  CBA: "#0f766e",
  CAS: "#7c3aed",
  CAUP: "#be185d",
  OSAS: "#c2410c",
  "Admin Office": "#374151",
  Library: "#0369a1",
  "IT Department": "#065f46",
};

function getDeptColor(dept) {
  return DEPT_COLORS[dept] || "#2d1b47";
}

function assetBadgeClass(status) {
  return (
    { Available: "available", "In Use": "in-use", Maintenance: "maintenance" }[
      status
    ] || "available"
  );
}

function borrowBadgeClass(status) {
  return (
    {
      Pending: "pending",
      Borrowed: "in-use",
      Returned: "available",
      Overdue: "overdue",
      Cancelled: "maintenance",
    }[status] || "pending"
  );
}

function maintBadgeClass(status) {
  return (
    {
      Pending: "pending",
      "In Progress": "in-use",
      Completed: "available",
      Cancelled: "maintenance",
    }[status] || "pending"
  );
}

// ─── LOAD ALL ─────────────────────────────────────────────────────────────────

function loadDashboard() {
  loadDashAssets();
  loadDashBorrows();
  loadDashMaintenance();
  loadDashUsers();
  loadDashDepts();
  loadDashReports();
}

// ─── LOAD ASSETS (existing) ───────────────────────────────────────────────────

function loadDashAssets() {
  fetch(ASSETS_API + "?resource=assets&action=getAll&_=" + Date.now())
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        dashAssets = data.data || [];
        renderDashAssetTable();
        updateDashStats();
      }
    })
    .catch(function () {
      showToast("⚠ Failed to load assets.");
    });
}

// ─── LOAD BORROWS (existing) ──────────────────────────────────────────────────

function loadDashBorrows() {
  fetch(BORROW_API + "?resource=borrows&action=getAll&_=" + Date.now())
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        dashBorrows = data.data || [];
        renderDashBorrowTable();
        updateDashStats();
      }
    })
    .catch(function () {
      showToast("⚠ Failed to load borrows.");
    });
}

// ─── LOAD MAINTENANCE (existing) ─────────────────────────────────────────────

function loadDashMaintenance() {
  fetch(MAINT_API + "?resource=maintenance&action=getAll&_=" + Date.now())
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        dashMaintenance = data.data || [];
        renderDashMaintTable();
        updateDashStats();
      }
    })
    .catch(function () {
      showToast("⚠ Failed to load maintenance.");
    });
}

// ─── LOAD USERS (new) ─────────────────────────────────────────────────────────

function loadDashUsers() {
  fetch(USERS_API + "?action=count&_=" + Date.now())
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        set("dashStatUsers", data.data.count || 0);
      }
    })
    .catch(function () {
      // Non-fatal: silently zero out if route not yet wired
      set("dashStatUsers", "—");
    });
}

// ─── LOAD DEPARTMENTS COUNT (new) ────────────────────────────────────────────
// Reuses the existing departments endpoint already in assets_route.php

function loadDashDepts() {
  fetch(ASSETS_API + "?resource=departments&_=" + Date.now())
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        set("dashStatDepts", (data.data || []).length);
      }
    })
    .catch(function () {
      set("dashStatDepts", "—");
    });
}

// ─── LOAD RECENT REPORTS (new) ───────────────────────────────────────────────

function loadDashReports() {
  fetch(REPORTS_API + "?resource=recent_reports&_=" + Date.now())
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        renderDashReportsTable((data.data && data.data.reports) || []);
      } else {
        renderDashReportsTable([]);
      }
    })
    .catch(function () {
      renderDashReportsTable([]);
    });
}

// ─── UPDATE STATS (existing + new governance stats) ──────────────────────────

function updateDashStats() {
  // All assets (including pending-delete ones for the system total)
  var allAssets = dashAssets;
  // Active (non-deleted) assets for operational stats
  var activeAssets = dashAssets.filter(function (a) {
    return a.IS_DELETED == 0;
  });

  // Governance
  var pendingDelete = allAssets.filter(function (a) {
    return a.IS_DELETED == 1;
  }).length;
  set("dashStatSystemAssets", allAssets.length);
  set("dashStatPendingDelete", pendingDelete);

  // Highlight the card if there are pending deletes
  var card = document.getElementById("pendingDeleteCard");
  if (card) {
    card.classList.toggle("stat-card--alert-active", pendingDelete > 0);
  }

  // Operational asset stats (active only)
  var total = activeAssets.length;
  var available = activeAssets.filter(function (a) {
    return a.STATUS === "Available";
  }).length;
  var inUse = activeAssets.filter(function (a) {
    return a.STATUS === "In Use";
  }).length;
  var maintenance = activeAssets.filter(function (a) {
    return a.STATUS === "Maintenance";
  }).length;

  set("dashStatTotal", total);
  set("dashStatAvailable", available);
  set("dashStatInUse", inUse);
  set("dashStatMaintenance", maintenance);

  // Borrow stats
  var pendingBorrows = dashBorrows.filter(function (b) {
    return b.STATUS === "Pending";
  }).length;
  var activeBorrows = dashBorrows.filter(function (b) {
    return b.STATUS === "Borrowed";
  }).length;
  var overdueBorrows = dashBorrows.filter(function (b) {
    if (b.STATUS === "Overdue") return true;
    if (b.STATUS === "Returned" && b.RETURN_DATE && b.DUE_DATE) {
      return b.RETURN_DATE > b.DUE_DATE;
    }
    return false;
  }).length;

  var pendingMaint = dashMaintenance.filter(function (m) {
    return m.STATUS === "Pending";
  }).length;

  set("dashStatPendingBorrows", pendingBorrows);
  set("dashStatActiveBorrows", activeBorrows);
  set("dashStatOverdue", overdueBorrows);
  set("dashStatPendingMaint", pendingMaint);
}

// ─── RENDER ASSET TABLE (existing, latest 5 active assets) ───────────────────

function renderDashAssetTable() {
  var tbody = document.getElementById("dashAssetTableBody");
  if (!tbody) return;

  var latest = dashAssets
    .filter(function (a) {
      return a.IS_DELETED == 0;
    })
    .slice(0, 5);

  if (!latest.length) {
    tbody.innerHTML =
      '<tr class="empty-row"><td colspan="5">No assets to display.</td></tr>';
    return;
  }

  tbody.innerHTML = latest
    .map(function (a) {
      var deptColor = getDeptColor(a.DEPARTMENT_NAME);
      return (
        "<tr>" +
        "<td><strong>" +
        (a.ASSET_ID || "—") +
        "</strong></td>" +
        "<td>" +
        (a.DESCRIPTION || "—") +
        "</td>" +
        '<td><span style="color:' +
        deptColor +
        ';font-weight:600;">' +
        (a.DEPARTMENT_NAME || "—") +
        "</span></td>" +
        '<td><span class="badge ' +
        assetBadgeClass(a.STATUS) +
        '">' +
        a.STATUS +
        "</span></td>" +
        "<td>" +
        formatDate(a.CREATED_AT) +
        "</td>" +
        "</tr>"
      );
    })
    .join("");
}

// ─── RENDER BORROW TABLE (existing, latest 5) ────────────────────────────────

function renderDashBorrowTable() {
  var tbody = document.getElementById("dashBorrowTableBody");
  if (!tbody) return;

  var latest = dashBorrows.slice(0, 5);

  if (!latest.length) {
    tbody.innerHTML =
      '<tr class="empty-row"><td colspan="5">No borrow requests to display.</td></tr>';
    return;
  }

  tbody.innerHTML = latest
    .map(function (b) {
      var borrower =
        [b.FIRST_NAME, b.MIDDLE_NAME, b.LAST_NAME, b.SUFFIX]
          .filter(Boolean)
          .join(" ") || "—";
      return (
        "<tr>" +
        "<td><strong>" +
        (b.BORROW_ID || "—") +
        "</strong></td>" +
        "<td>" +
        borrower +
        "</td>" +
        "<td>" +
        (b.ASSET_ID || "—") +
        "</td>" +
        "<td>" +
        formatDate(b.BORROW_DATE) +
        "</td>" +
        '<td><span class="badge ' +
        borrowBadgeClass(b.STATUS) +
        '">' +
        b.STATUS +
        "</span></td>" +
        "</tr>"
      );
    })
    .join("");
}

// ─── RENDER MAINTENANCE TABLE (existing, latest 5) ───────────────────────────

function renderDashMaintTable() {
  var tbody = document.getElementById("dashMaintTableBody");
  if (!tbody) return;

  var latest = dashMaintenance.slice(0, 5);

  if (!latest.length) {
    tbody.innerHTML =
      '<tr class="empty-row"><td colspan="5">No maintenance records to display.</td></tr>';
    return;
  }

  tbody.innerHTML = latest
    .map(function (m) {
      return (
        "<tr>" +
        "<td><strong>" +
        (m.MAINTENANCE_ID || "—") +
        "</strong></td>" +
        "<td>" +
        (m.ASSET_ID || "—") +
        "</td>" +
        "<td>" +
        (m.MAINTENANCE_TYPE || "—") +
        "</td>" +
        "<td>" +
        formatDate(m.SCHEDULED_DATE) +
        "</td>" +
        '<td><span class="badge ' +
        maintBadgeClass(m.STATUS) +
        '">' +
        m.STATUS +
        "</span></td>" +
        "</tr>"
      );
    })
    .join("");
}

// ─── RENDER REPORTS TABLE (new, latest 5) ────────────────────────────────────

function renderDashReportsTable(reports) {
  var tbody = document.getElementById("dashReportsTableBody");
  if (!tbody) return;

  var latest = reports.slice(0, 5);

  if (!latest.length) {
    tbody.innerHTML =
      '<tr class="empty-row"><td colspan="5">No reports generated yet.</td></tr>';
    return;
  }

  tbody.innerHTML = latest
    .map(function (r) {
      var name = r.REPORT_NAME || r.report_name || "—";
      var type = r.REPORT_TYPE || r.report_type || "—";
      var generatedBy = r.GENERATED_BY || r.generated_by || "—";
      var dateStr =
        r.GENERATED_AT ||
        r.generated_at ||
        r.CREATED_AT ||
        r.created_at ||
        null;
      var format = r.FORMAT || r.format || "PDF";

      return (
        "<tr>" +
        "<td><strong>" +
        name +
        "</strong></td>" +
        "<td>" +
        type +
        "</td>" +
        "<td>" +
        generatedBy +
        "</td>" +
        "<td>" +
        formatDate(dateStr) +
        "</td>" +
        '<td><span class="badge-format">' +
        format +
        "</span></td>" +
        "</tr>"
      );
    })
    .join("");
}

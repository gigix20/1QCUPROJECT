// ── API ROUTES ────────────────────────────────────────────────────────────────
var DEPT_API = "/1QCUPROJECT/backend/routes/departments_route.php";
var CUST_API = "/1QCUPROJECT/backend/routes/custodians_route.php";

// ── DATA STORES ───────────────────────────────────────────────────────────────
var departments = [];
var custodians = [];

// ─── UTILITIES ────────────────────────────────────────────────────────────────

function showToast(msg) {
  var el = document.getElementById("toast");
  if (!el) return;
  el.textContent = msg;
  el.classList.add("show");
  setTimeout(function () {
    el.classList.remove("show");
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

// ─── STATS ────────────────────────────────────────────────────────────────────

function updateStats() {
  var total = departments.length;
  var buildings = new Set(
    departments
      .map(function (d) {
        return d.BUILDING;
      })
      .filter(Boolean)
  ).size;
  var active = departments.filter(function (d) {
    return (d.STATUS || "Active") === "Active";
  }).length;
  var totalAssets = departments.reduce(function (sum, d) {
    return sum + (parseInt(d.ASSET_COUNT) || 0);
  }, 0);

  var set = function (id, v) {
    var el = document.getElementById(id);
    if (el) el.textContent = v;
  };
  set("statTotalDepts", total);
  set("statBuildings", buildings);
  set("statActiveDepts", active);
  set("statTotalAssets", totalAssets);
}

// ─── RENDER DEPARTMENTS TABLE ─────────────────────────────────────────────────

function renderDeptTable(filter) {
  var tbody = document.getElementById("deptTableBody");
  if (!tbody) return;
  filter = (filter || "").toLowerCase();

  var filtered = departments.filter(function (d) {
    var name = (d.DEPARTMENT_NAME || "").toLowerCase();
    var build = (d.BUILDING || "").toLowerCase();
    var head = (d.DEPARTMENT_HEAD || "").toLowerCase();
    return (
      !filter ||
      name.includes(filter) ||
      build.includes(filter) ||
      head.includes(filter)
    );
  });

  if (!filtered.length) {
    tbody.innerHTML =
      '<tr class="empty-row"><td colspan="6">No departments to display.</td></tr>';
    return;
  }

  tbody.innerHTML = filtered
    .map(function (d) {
      var id = d.DEPARTMENT_ID;
      var assetCount = parseInt(d.ASSET_COUNT) || 0;
      var statusClass =
        (d.STATUS || "Active") === "Active" ? "available" : "maintenance";
      var statusLabel = d.STATUS || "Active";

      return (
        "<tr>" +
        "<td><strong>" +
        (d.DEPARTMENT_NAME || "—") +
        "</strong></td>" +
        "<td>" +
        (d.BUILDING || "—") +
        "</td>" +
        "<td>" +
        (d.DEPARTMENT_HEAD || "—") +
        "</td>" +
        '<td style="text-align:center;font-weight:600;color:#5b21b6;">' +
        assetCount +
        "</td>" +
        '<td><span class="badge ' +
        statusClass +
        '">' +
        statusLabel +
        "</span></td>" +
        "<td>" +
        '<div class="action-group">' +
        '<button class="edit-btn" data-id="' +
        id +
        '">Edit</button>' +
        '<button class="del-btn" data-id="' +
        id +
        '" data-name="' +
        (d.DEPARTMENT_NAME || "").replace(/"/g, "&quot;") +
        '" data-assets="' +
        assetCount +
        '">Delete</button>' +
        "</div>" +
        "</td>" +
        "</tr>"
      );
    })
    .join("");
}

// ─── RENDER CUSTODIANS TABLE ──────────────────────────────────────────────────

function custodianFullName(c) {
  return (
    [c.FIRST_NAME, c.MIDDLE_NAME, c.LAST_NAME, c.SUFFIX]
      .filter(Boolean)
      .join(" ") || "—"
  );
}

function renderCustodianTable(filter) {
  var tbody = document.getElementById("custTableBody");
  if (!tbody) return;
  filter = (filter || "").toLowerCase();

  var filtered = custodians.filter(function (c) {
    var name = custodianFullName(c).toLowerCase();
    var dept = (c.DEPARTMENT_NAME || "").toLowerCase();
    var empId = (c.EMPLOYEE_ID || "").toLowerCase();
    var email = (c.EMAIL || "").toLowerCase();
    return (
      !filter ||
      name.includes(filter) ||
      dept.includes(filter) ||
      empId.includes(filter) ||
      email.includes(filter)
    );
  });

  if (!filtered.length) {
    tbody.innerHTML =
      '<tr class="empty-row"><td colspan="7">No custodians to display.</td></tr>';
    return;
  }

  tbody.innerHTML = filtered
    .map(function (c) {
      var id = c.CUSTODIAN_ID;
      var statusClass =
        (c.STATUS || "Active") === "Active" ? "available" : "maintenance";
      var statusLabel = c.STATUS || "Active";
      var assetCount = parseInt(c.ASSET_COUNT) || 0;

      return (
        "<tr>" +
        "<td><strong>" +
        custodianFullName(c) +
        "</strong></td>" +
        "<td>" +
        (c.EMPLOYEE_ID || "—") +
        "</td>" +
        "<td>" +
        (c.DEPARTMENT_NAME || "—") +
        "</td>" +
        "<td>" +
        (c.EMAIL || "—") +
        "</td>" +
        "<td>" +
        (c.PHONE || "—") +
        "</td>" +
        '<td style="text-align:center;font-weight:600;color:#5b21b6;">' +
        assetCount +
        "</td>" +
        '<td><span class="badge ' +
        statusClass +
        '">' +
        statusLabel +
        "</span></td>" +
        "<td>" +
        '<div class="action-group">' +
        '<button class="edit-btn cust-edit-btn" data-id="' +
        id +
        '">Edit</button>' +
        '<button class="del-btn cust-del-btn" data-id="' +
        id +
        '" data-name="' +
        custodianFullName(c).replace(/"/g, "&quot;") +
        '" data-assets="' +
        assetCount +
        '">Delete</button>' +
        "</div>" +
        "</td>" +
        "</tr>"
      );
    })
    .join("");
}

// ─── LOAD DATA ────────────────────────────────────────────────────────────────

function loadDepartments() {
  fetch(DEPT_API + "?action=getAll&_=" + Date.now())
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        departments = data.data || [];
        var s = document.getElementById("deptSearchInput");
        renderDeptTable(s ? s.value : "");
        updateStats();
      } else {
        showToast("⚠ Failed to load departments.");
      }
    })
    .catch(function () {
      showToast("⚠ Error connecting to server.");
    });
}

function loadCustodians() {
  fetch(CUST_API + "?action=getAll&_=" + Date.now())
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        custodians = data.data || [];
        var s = document.getElementById("custSearchInput");
        renderCustodianTable(s ? s.value : "");
      } else {
        showToast("⚠ Failed to load custodians.");
      }
    })
    .catch(function () {
      showToast("⚠ Error connecting to server.");
    });
}

function loadAll() {
  loadDepartments();
  loadCustodians();
}

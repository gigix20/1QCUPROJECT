// ─── SHARED HELPERS ───────────────────────────────────────────────────────────

function getVal(id) {
  var el = document.getElementById(id);
  return el ? el.value.trim() : "";
}

function setVal(id, val) {
  var el = document.getElementById(id);
  if (el) el.value = val || "";
}

function clearFormErrors(modalId) {
  var modal = document.getElementById(modalId);
  if (!modal) return;
  modal.querySelectorAll(".dept-form-error").forEach(function (el) { el.textContent = ""; });
}

function showFieldError(id, msg) {
  var el = document.getElementById(id);
  if (el) el.textContent = msg;
}

// ─── DEPARTMENT: ADD MODAL ────────────────────────────────────────────────────

function clearAddDeptForm() {
  ["addDeptName", "addBuilding", "addDeptHead"].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.value = "";
  });
  var statusEl = document.getElementById("addStatus");
  if (statusEl) statusEl.value = "Active";
  clearFormErrors("addDeptModal");
}

function openAddDeptModal() {
  clearAddDeptForm();
  openModal("addDeptModal");
  var f = document.getElementById("addDeptName");
  if (f) f.focus();
}

function submitAddDept() {
  clearFormErrors("addDeptModal");

  var name   = getVal("addDeptName");
  var build  = getVal("addBuilding");
  var head   = getVal("addDeptHead");
  var status = getVal("addStatus") || "Active";

  if (!name) { showFieldError("errAddName", "Department name is required."); return; }

  var formData = new FormData();
  formData.append("action",          "add");
  formData.append("department_name", name);
  formData.append("building",        build);
  formData.append("department_head", head);
  formData.append("status",          status);

  fetch(DEPT_API, { method: "POST", body: formData })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.status === "success") {
        closeModal("addDeptModal");
        loadDepartments();
        showToast("✓ Department added successfully.");
      } else {
        showToast("⚠ " + data.message);
      }
    })
    .catch(function () { showToast("⚠ Error connecting to server."); });
}

// ─── DEPARTMENT: EDIT MODAL ───────────────────────────────────────────────────

function openEditDeptModal(department_id) {
  fetch(DEPT_API + "?action=getById&department_id=" + department_id + "&_=" + Date.now())
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.status !== "success") { showToast("⚠ Department not found."); return; }
      var d = data.data;
      setVal("editDeptId",   d.DEPARTMENT_ID);
      setVal("editDeptName", d.DEPARTMENT_NAME);
      setVal("editBuilding", d.BUILDING);
      setVal("editDeptHead", d.DEPARTMENT_HEAD);
      setVal("editStatus",   d.STATUS || "Active");
      clearFormErrors("editDeptModal");
      openModal("editDeptModal");
      var f = document.getElementById("editDeptName");
      if (f) f.focus();
    })
    .catch(function () { showToast("⚠ Error connecting to server."); });
}

function submitEditDept() {
  clearFormErrors("editDeptModal");

  var id     = getVal("editDeptId");
  var name   = getVal("editDeptName");
  var build  = getVal("editBuilding");
  var head   = getVal("editDeptHead");
  var status = getVal("editStatus") || "Active";

  if (!name) { showFieldError("errEditName", "Department name is required."); return; }

  var formData = new FormData();
  formData.append("action",          "update");
  formData.append("department_id",   id);
  formData.append("department_name", name);
  formData.append("building",        build);
  formData.append("department_head", head);
  formData.append("status",          status);

  fetch(DEPT_API, { method: "POST", body: formData })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.status === "success") {
        closeModal("editDeptModal");
        loadDepartments();
        showToast("✓ Department updated successfully.");
      } else {
        showToast("⚠ " + data.message);
      }
    })
    .catch(function () { showToast("⚠ Error connecting to server."); });
}

// ─── DEPARTMENT: DELETE MODAL ─────────────────────────────────────────────────

function openDeleteDeptModal(department_id, department_name, asset_count) {
  var modal = document.getElementById("deleteDeptModal");
  if (!modal) return;
  modal.setAttribute("data-delete-id", department_id);

  var nameEl = document.getElementById("deleteDeptName");
  if (nameEl) nameEl.textContent = department_name;

  var warningEl = document.getElementById("deleteAssetWarning");
  var confirmEl = document.getElementById("confirmDeleteDeptBtn");

  if (asset_count > 0) {
    if (warningEl) {
      warningEl.style.display = "";
      warningEl.textContent = "⚠ This department has " + asset_count + " active asset(s) assigned. Remove or reassign them before deleting.";
    }
    if (confirmEl) confirmEl.disabled = true;
  } else {
    if (warningEl) warningEl.style.display = "none";
    if (confirmEl) confirmEl.disabled = false;
  }
  openModal("deleteDeptModal");
}

function submitDeleteDept() {
  var modal = document.getElementById("deleteDeptModal");
  var id    = modal.getAttribute("data-delete-id");

  var formData = new FormData();
  formData.append("action",        "delete");
  formData.append("department_id", id);

  fetch(DEPT_API, { method: "POST", body: formData })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      closeModal("deleteDeptModal");
      if (data.status === "success") {
        loadDepartments();
        showToast("🗑 Department deleted.");
      } else {
        showToast("⚠ " + data.message);
      }
    })
    .catch(function () { showToast("⚠ Error connecting to server."); });
}

// ─── CUSTODIAN: DEPARTMENT DROPDOWN HELPER ────────────────────────────────────

function populateDeptDropdown(selectId, selectedId) {
  var select = document.getElementById(selectId);
  if (!select) return;
  select.innerHTML = '<option value="">-- Select Department --</option>';
  departments.forEach(function (d) {
    var opt      = document.createElement("option");
    opt.value    = d.DEPARTMENT_ID;
    opt.textContent = d.DEPARTMENT_NAME;
    if (String(d.DEPARTMENT_ID) === String(selectedId)) opt.selected = true;
    select.appendChild(opt);
  });
}

// ─── CUSTODIAN: ADD MODAL ─────────────────────────────────────────────────────

function clearAddCustForm() {
  ["addCustFirst", "addCustMiddle", "addCustLast", "addCustSuffix",
   "addCustEmpId", "addCustEmail", "addCustPhone"].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.value = "";
  });
  var statusEl = document.getElementById("addCustStatus");
  if (statusEl) statusEl.value = "Active";
  clearFormErrors("addCustModal");
}

function openAddCustModal() {
  clearAddCustForm();
  populateDeptDropdown("addCustDept", null);
  openModal("addCustModal");
  var f = document.getElementById("addCustFirst");
  if (f) f.focus();
}

function submitAddCust() {
  clearFormErrors("addCustModal");

  var dept   = getVal("addCustDept");
  var first  = getVal("addCustFirst");
  var middle = getVal("addCustMiddle");
  var last   = getVal("addCustLast");
  var suffix = getVal("addCustSuffix");
  var empId  = getVal("addCustEmpId");
  var email  = getVal("addCustEmail");
  var phone  = getVal("addCustPhone");
  var status = getVal("addCustStatus") || "Active";

  var valid = true;
  if (!dept)  { showFieldError("errAddCustDept",  "Department is required.");  valid = false; }
  if (!first) { showFieldError("errAddCustFirst", "First name is required.");  valid = false; }
  if (!last)  { showFieldError("errAddCustLast",  "Last name is required.");   valid = false; }
  if (!valid) return;

  var formData = new FormData();
  formData.append("action",        "add");
  formData.append("department_id", dept);
  formData.append("first_name",    first);
  formData.append("middle_name",   middle);
  formData.append("last_name",     last);
  formData.append("suffix",        suffix);
  formData.append("employee_id",   empId);
  formData.append("email",         email);
  formData.append("phone",         phone);
  formData.append("status",        status);

  fetch(CUST_API, { method: "POST", body: formData })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.status === "success") {
        closeModal("addCustModal");
        loadCustodians();
        showToast("✓ Custodian added successfully.");
      } else {
        showToast("⚠ " + data.message);
      }
    })
    .catch(function () { showToast("⚠ Error connecting to server."); });
}

// ─── CUSTODIAN: EDIT MODAL ────────────────────────────────────────────────────

function openEditCustModal(custodian_id) {
  fetch(CUST_API + "?action=getById&custodian_id=" + custodian_id + "&_=" + Date.now())
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.status !== "success") { showToast("⚠ Custodian not found."); return; }
      var c = data.data;
      populateDeptDropdown("editCustDept", c.DEPARTMENT_ID);
      setVal("editCustId",     c.CUSTODIAN_ID);
      setVal("editCustFirst",  c.FIRST_NAME);
      setVal("editCustMiddle", c.MIDDLE_NAME);
      setVal("editCustLast",   c.LAST_NAME);
      setVal("editCustSuffix", c.SUFFIX);
      setVal("editCustEmpId",  c.EMPLOYEE_ID);
      setVal("editCustEmail",  c.EMAIL);
      setVal("editCustPhone",  c.PHONE);
      setVal("editCustStatus", c.STATUS || "Active");
      clearFormErrors("editCustModal");
      openModal("editCustModal");
      var f = document.getElementById("editCustFirst");
      if (f) f.focus();
    })
    .catch(function () { showToast("⚠ Error connecting to server."); });
}

function submitEditCust() {
  clearFormErrors("editCustModal");

  var id     = getVal("editCustId");
  var dept   = getVal("editCustDept");
  var first  = getVal("editCustFirst");
  var middle = getVal("editCustMiddle");
  var last   = getVal("editCustLast");
  var suffix = getVal("editCustSuffix");
  var empId  = getVal("editCustEmpId");
  var email  = getVal("editCustEmail");
  var phone  = getVal("editCustPhone");
  var status = getVal("editCustStatus") || "Active";

  var valid = true;
  if (!dept)  { showFieldError("errEditCustDept",  "Department is required.");  valid = false; }
  if (!first) { showFieldError("errEditCustFirst", "First name is required.");  valid = false; }
  if (!last)  { showFieldError("errEditCustLast",  "Last name is required.");   valid = false; }
  if (!valid) return;

  var formData = new FormData();
  formData.append("action",        "update");
  formData.append("custodian_id",  id);
  formData.append("department_id", dept);
  formData.append("first_name",    first);
  formData.append("middle_name",   middle);
  formData.append("last_name",     last);
  formData.append("suffix",        suffix);
  formData.append("employee_id",   empId);
  formData.append("email",         email);
  formData.append("phone",         phone);
  formData.append("status",        status);

  fetch(CUST_API, { method: "POST", body: formData })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.status === "success") {
        closeModal("editCustModal");
        loadCustodians();
        showToast("✓ Custodian updated successfully.");
      } else {
        showToast("⚠ " + data.message);
      }
    })
    .catch(function () { showToast("⚠ Error connecting to server."); });
}

// ─── CUSTODIAN: DELETE MODAL ──────────────────────────────────────────────────

function openDeleteCustModal(custodian_id, custodian_name, asset_count) {
  var modal = document.getElementById("deleteCustModal");
  if (!modal) return;
  modal.setAttribute("data-delete-id", custodian_id);

  var nameEl = document.getElementById("deleteCustName");
  if (nameEl) nameEl.textContent = custodian_name;

  var warningEl = document.getElementById("deleteCustAssetWarning");
  if (asset_count > 0) {
    if (warningEl) {
      warningEl.style.display = "";
      warningEl.textContent = "⚠ This custodian has " + asset_count + " asset(s) assigned. They will be unassigned upon deletion.";
    }
  } else {
    if (warningEl) warningEl.style.display = "none";
  }
  openModal("deleteCustModal");
}

function submitDeleteCust() {
  var modal = document.getElementById("deleteCustModal");
  var id    = modal.getAttribute("data-delete-id");

  var formData = new FormData();
  formData.append("action",       "delete");
  formData.append("custodian_id", id);

  fetch(CUST_API, { method: "POST", body: formData })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      closeModal("deleteCustModal");
      if (data.status === "success") {
        loadCustodians();
        showToast("🗑 " + data.message);
      } else {
        showToast("⚠ " + data.message);
      }
    })
    .catch(function () { showToast("⚠ Error connecting to server."); });
}
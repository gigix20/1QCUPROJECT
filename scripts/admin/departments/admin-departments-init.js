document.addEventListener("DOMContentLoaded", function () {

  // ── DEPARTMENT: Add ────────────────────────────────────────────────────────
  var openAddDeptBtn = document.getElementById("openAddDeptBtn");
  if (openAddDeptBtn) openAddDeptBtn.addEventListener("click", openAddDeptModal);

  var cancelAddDeptBtn = document.getElementById("cancelAddDeptBtn");
  if (cancelAddDeptBtn) cancelAddDeptBtn.addEventListener("click", function () { closeModal("addDeptModal"); });

  var saveAddDeptBtn = document.getElementById("saveAddDeptBtn");
  if (saveAddDeptBtn) saveAddDeptBtn.addEventListener("click", submitAddDept);

  // ── DEPARTMENT: Edit ───────────────────────────────────────────────────────
  var cancelEditDeptBtn = document.getElementById("cancelEditDeptBtn");
  if (cancelEditDeptBtn) cancelEditDeptBtn.addEventListener("click", function () { closeModal("editDeptModal"); });

  var saveEditDeptBtn = document.getElementById("saveEditDeptBtn");
  if (saveEditDeptBtn) saveEditDeptBtn.addEventListener("click", submitEditDept);

  // ── DEPARTMENT: Delete ─────────────────────────────────────────────────────
  var cancelDeleteDeptBtn = document.getElementById("cancelDeleteDeptBtn");
  if (cancelDeleteDeptBtn) cancelDeleteDeptBtn.addEventListener("click", function () { closeModal("deleteDeptModal"); });

  var confirmDeleteDeptBtn = document.getElementById("confirmDeleteDeptBtn");
  if (confirmDeleteDeptBtn) confirmDeleteDeptBtn.addEventListener("click", submitDeleteDept);

  // ── DEPARTMENT: Table delegation ───────────────────────────────────────────
  var deptTableBody = document.getElementById("deptTableBody");
  if (deptTableBody) {
    deptTableBody.addEventListener("click", function (e) {
      var editBtn = e.target.closest(".edit-btn");
      if (editBtn && !editBtn.classList.contains("cust-edit-btn")) {
        openEditDeptModal(editBtn.dataset.id);
        return;
      }
      var delBtn = e.target.closest(".del-btn");
      if (delBtn && !delBtn.classList.contains("cust-del-btn")) {
        openDeleteDeptModal(delBtn.dataset.id, delBtn.dataset.name, parseInt(delBtn.dataset.assets) || 0);
      }
    });
  }

  // ── DEPARTMENT: Search ─────────────────────────────────────────────────────
  var deptSearchInput = document.getElementById("deptSearchInput");
  if (deptSearchInput) {
    deptSearchInput.addEventListener("input", function () { renderDeptTable(this.value); });
  }

  // ── CUSTODIAN: Add ─────────────────────────────────────────────────────────
  var openAddCustBtn = document.getElementById("openAddCustBtn");
  if (openAddCustBtn) openAddCustBtn.addEventListener("click", openAddCustModal);

  var cancelAddCustBtn = document.getElementById("cancelAddCustBtn");
  if (cancelAddCustBtn) cancelAddCustBtn.addEventListener("click", function () { closeModal("addCustModal"); });

  var saveAddCustBtn = document.getElementById("saveAddCustBtn");
  if (saveAddCustBtn) saveAddCustBtn.addEventListener("click", submitAddCust);

  // ── CUSTODIAN: Edit ────────────────────────────────────────────────────────
  var cancelEditCustBtn = document.getElementById("cancelEditCustBtn");
  if (cancelEditCustBtn) cancelEditCustBtn.addEventListener("click", function () { closeModal("editCustModal"); });

  var saveEditCustBtn = document.getElementById("saveEditCustBtn");
  if (saveEditCustBtn) saveEditCustBtn.addEventListener("click", submitEditCust);

  // ── CUSTODIAN: Delete ──────────────────────────────────────────────────────
  var cancelDeleteCustBtn = document.getElementById("cancelDeleteCustBtn");
  if (cancelDeleteCustBtn) cancelDeleteCustBtn.addEventListener("click", function () { closeModal("deleteCustModal"); });

  var confirmDeleteCustBtn = document.getElementById("confirmDeleteCustBtn");
  if (confirmDeleteCustBtn) confirmDeleteCustBtn.addEventListener("click", submitDeleteCust);

  // ── CUSTODIAN: Table delegation ────────────────────────────────────────────
  var custTableBody = document.getElementById("custTableBody");
  if (custTableBody) {
    custTableBody.addEventListener("click", function (e) {
      var editBtn = e.target.closest(".cust-edit-btn");
      if (editBtn) { openEditCustModal(editBtn.dataset.id); return; }

      var delBtn = e.target.closest(".cust-del-btn");
      if (delBtn) {
        openDeleteCustModal(delBtn.dataset.id, delBtn.dataset.name, parseInt(delBtn.dataset.assets) || 0);
      }
    });
  }

  // ── CUSTODIAN: Search ──────────────────────────────────────────────────────
  var custSearchInput = document.getElementById("custSearchInput");
  if (custSearchInput) {
    custSearchInput.addEventListener("input", function () { renderCustodianTable(this.value); });
  }

  // ── CLOSE MODALS: outside click ────────────────────────────────────────────
  document.querySelectorAll(".modal-overlay").forEach(function (overlay) {
    overlay.addEventListener("click", function (e) {
      if (e.target === this) this.classList.remove("active");
    });
  });

  // ── CLOSE MODALS: Escape ───────────────────────────────────────────────────
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      document.querySelectorAll(".modal-overlay.active").forEach(function (m) {
        m.classList.remove("active");
      });
    }
  });

  // ── INITIAL LOAD ───────────────────────────────────────────────────────────
  loadAll();
});
/* ================================================================
FILTER DROPDOWNS — Category & Item Type
================================================================
HOW IT WORKS:
1. On page load, it reads unique Category & Item Type values
from the already-rendered table rows (populated by your
existing staff-assets.js).  Call refreshFilterOptions()
anytime the table re-renders (after add/edit/delete).
2. Clicking a dropdown item sets the active filter value.
3. applyDropdownFilters() hides/shows rows that don't match
BOTH the selected category AND the selected item type.
It works together with your existing status-tab filter.
================================================================ */

(function () {
  /* ── State ─────────────────────────────────────── */
  let selectedCategory = "";
  let selectedItemType = "";

  /* ── DOM refs ──────────────────────────────────── */
  const categoryBtn = document.getElementById("categoryFilterBtn");
  const categoryPanel = document.getElementById("categoryDropdownPanel");
  const categoryBadge = document.getElementById("categoryBadge");

  const itemTypeBtn = document.getElementById("itemTypeFilterBtn");
  const itemTypePanel = document.getElementById("itemTypeDropdownPanel");
  const itemTypeBadge = document.getElementById("itemTypeBadge");

  const clearBtn = document.getElementById("clearFiltersBtn");

  /* ── Toggle panels ─────────────────────────────── */
  function togglePanel(btn, panel) {
    const isOpen = panel.classList.contains("show");
    closeAll();
    if (!isOpen) {
      panel.classList.add("show");
      btn.classList.add("open");
    }
  }

  function closeAll() {
    [categoryPanel, itemTypePanel].forEach((p) => p.classList.remove("show"));
    [categoryBtn, itemTypeBtn].forEach((b) => b.classList.remove("open"));
  }

  categoryBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    togglePanel(categoryBtn, categoryPanel);
  });

  itemTypeBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    togglePanel(itemTypeBtn, itemTypePanel);
  });

  document.addEventListener("click", closeAll);

  /* ── Build dropdown items from unique table values ─ */
  function buildOptions(panel, values, currentValue, onSelect) {
    // Remove old dynamic items (keep "All" + divider = first 2 nodes)
    const all = panel.children[0];
    const divider = panel.children[1];
    panel.innerHTML = "";
    panel.appendChild(all);
    panel.appendChild(divider);

    values.forEach((val) => {
      const item = document.createElement("div");
      item.className = "ddp-item" + (val === currentValue ? " selected" : "");
      item.dataset.value = val;
      item.innerHTML = `
                    <svg class="ddp-check" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    ${val}`;
      item.addEventListener("click", (e) => {
        e.stopPropagation();
        onSelect(val);
        closeAll();
      });
      panel.appendChild(item);
    });

    // "All" item click
    all.onclick = (e) => {
      e.stopPropagation();
      onSelect("");
      closeAll();
    };
  }

  /* ── Refresh options from current table rows ───── */
  window.refreshFilterOptions = function () {
    const rows = document.querySelectorAll(
      "#assetsTableBody tr:not(.empty-row)"
    );

    const categories = new Set();
    const itemTypes = new Set();

    rows.forEach((row) => {
      const cells = row.querySelectorAll("td");
      // Col indices based on your table: 0=AssetID,1=QR,2=Desc,3=Serial,4=ItemType,5=Category...
      if (cells[4]) itemTypes.add(cells[4].textContent.trim());
      if (cells[5]) categories.add(cells[5].textContent.trim());
    });

    buildOptions(
      categoryPanel,
      [...categories].filter(Boolean).sort(),
      selectedCategory,
      (val) => {
        selectedCategory = val;
        updateCategoryUI();
        applyDropdownFilters();
      }
    );

    buildOptions(
      itemTypePanel,
      [...itemTypes].filter(Boolean).sort(),
      selectedItemType,
      (val) => {
        selectedItemType = val;
        updateItemTypeUI();
        applyDropdownFilters();
      }
    );
  };

  /* ── Update button appearance ───────────────────── */
  function updateCategoryUI() {
    const hasFilter = selectedCategory !== "";
    categoryBtn.classList.toggle("active-filter", hasFilter);
    categoryBadge.style.display = hasFilter ? "inline-flex" : "none";
    categoryBadge.textContent = hasFilter ? "1" : "";
    // Update "All Categories" selected state
    categoryPanel.querySelectorAll(".ddp-item").forEach((i) => {
      i.classList.toggle("selected", i.dataset.value === selectedCategory);
    });
    updateClearBtn();
  }

  function updateItemTypeUI() {
    const hasFilter = selectedItemType !== "";
    itemTypeBtn.classList.toggle("active-filter", hasFilter);
    itemTypeBadge.style.display = hasFilter ? "inline-flex" : "none";
    itemTypeBadge.textContent = hasFilter ? "1" : "";
    itemTypePanel.querySelectorAll(".ddp-item").forEach((i) => {
      i.classList.toggle("selected", i.dataset.value === selectedItemType);
    });
    updateClearBtn();
  }

  function updateClearBtn() {
    const hasAny = selectedCategory !== "" || selectedItemType !== "";
    clearBtn.classList.toggle("visible", hasAny);
  }

  /* ── Clear all ──────────────────────────────────── */
  clearBtn.addEventListener("click", () => {
    selectedCategory = "";
    selectedItemType = "";
    updateCategoryUI();
    updateItemTypeUI();
    applyDropdownFilters();
  });

  /* ── Apply filter to table rows ─────────────────── */
  window.applyDropdownFilters = function () {
    const rows = document.querySelectorAll(
      "#assetsTableBody tr:not(.empty-row)"
    );

    rows.forEach((row) => {
      const cells = row.querySelectorAll("td");
      const rowItemType = cells[4] ? cells[4].textContent.trim() : "";
      const rowCategory = cells[5] ? cells[5].textContent.trim() : "";

      const matchCategory =
        selectedCategory === "" || rowCategory === selectedCategory;
      const matchItemType =
        selectedItemType === "" || rowItemType === selectedItemType;

      // Only touch display if the row is already "visible" by status filter;
      // use a data attribute approach so both filters compose cleanly.
      if (!matchCategory || !matchItemType) {
        row.setAttribute("data-dropdown-hidden", "1");
        row.style.display = "none";
      } else {
        row.removeAttribute("data-dropdown-hidden");
        // Restore visibility only if status filter hasn't hidden it
        if (!row.getAttribute("data-status-hidden")) {
          row.style.display = "";
        }
      }
    });

    showEmptyIfNeeded();
  };

  /* ── Show empty row if everything is filtered out ─ */
  function showEmptyIfNeeded() {
    const tbody = document.getElementById("assetsTableBody");
    const visible = [...tbody.querySelectorAll("tr:not(.empty-row)")].filter(
      (r) => r.style.display !== "none"
    );
    let emptyRow = tbody.querySelector(".empty-row");
    if (visible.length === 0) {
      if (!emptyRow) {
        emptyRow = document.createElement("tr");
        emptyRow.className = "empty-row";
        emptyRow.innerHTML =
          '<td colspan="11">No assets match the selected filters.</td>';
        tbody.appendChild(emptyRow);
      }
      emptyRow.style.display = "";
    } else {
      if (emptyRow) emptyRow.style.display = "none";
    }
  }

  /* ── Hook into table re-renders ─────────────────── */
  // Watch for changes to the table body so options stay fresh
  const observer = new MutationObserver(() => {
    window.refreshFilterOptions();
    window.applyDropdownFilters();
  });
  observer.observe(document.getElementById("assetsTableBody"), {
    childList: true,
    subtree: true,
  });

  // Initial build
  window.refreshFilterOptions();
})();

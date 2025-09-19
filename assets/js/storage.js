// Storage Management
const DB_KEY = "pt:min";

function readDB() {
  try {
    return JSON.parse(localStorage.getItem(DB_KEY)) || { products: {} };
  } catch (e) {
    return { products: {} };
  }
}

function writeDB(db) {
  localStorage.setItem(DB_KEY, JSON.stringify(db));
}

// Export/Import Functions
function exportData() {
  const data = localStorage.getItem(DB_KEY) || "{}";
  const blob = new Blob([data], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'watchlist.json';
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
}

function importData(file) {
  const reader = new FileReader();
  reader.onload = function() {
    try {
      const obj = JSON.parse(reader.result);
      localStorage.setItem(DB_KEY, JSON.stringify(obj));
      refreshList();
      M.toast({ html: 'Imported' });
    } catch (_) {
      M.toast({ html: 'Invalid JSON' });
    }
  };
  reader.readAsText(file);
}
<!DOCTYPE html>
<style>
.edit-modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
}
.modal-content {
  background-color: #fefefe;
  margin: 5% auto;
  padding: 30px;
  border-radius: 10px;
  width: 90%;
  max-width: 500px;
  max-height: 80vh;
  overflow-y: auto;
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
.close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
.close:hover { color: #000; }
.form-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
.form-row > div { flex: 1; min-width: 200px; }
label { display: block; margin-bottom: 5px; font-weight: bold; }
input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
.btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
.btn-primary { background: #007bff; color: white; }
.btn-secondary { background: #6c757d; color: white; }
@media (max-width: 600px) { .form-row { flex-direction: column; } }
</style>

<div id="editModal" class="edit-modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3 id="modalTitle">Edit Entry</h3>
    <form id="editForm" method="POST">
      <input type="hidden" name="id" id="editId">
      <!-- Dynamic fields populated by JS -->
      <div id="modalBody"></div>
      <div style="text-align: right; margin-top: 20px;">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" name="edit_entry">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(title, fields, id = null, postAction = 'edit') {
  document.getElementById('modalTitle').textContent = title;
  document.getElementById('editId').value = id || '';
  document.querySelector('#editForm').setAttribute('action', postAction);
  
  const body = document.getElementById('modalBody');
  body.innerHTML = fields.map(f => `
    <div>
      <label>${f.label}: ${f.required ? '<span style="color:red;">*</span>' : ''}</label>
      <${f.type || 'input'} name="${f.name}" value="${f.value || ''}" ${f.required ? 'required' : ''} ${f.attrs || ''}>
    </div>
  `).join('');
  
  document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
  document.getElementById('editModal').style.display = 'none';
}

document.querySelector('.close').onclick = closeModal;
window.onclick = function(e) {
  if (e.target == document.getElementById('editModal')) closeModal();
};
</script>

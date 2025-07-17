// script.js

document.addEventListener('DOMContentLoaded', () => {
  const setorSelect = document.getElementById('setor');
  const usuarioSelect = document.getElementById('usuario');

  setorSelect.addEventListener('change', () => {
    const setorId = setorSelect.value;
    usuarioSelect.innerHTML = '<option>Carregando...</option>';
    usuarioSelect.disabled = true;

    if (!setorId) {
      usuarioSelect.innerHTML = '<option>Selecione o setor primeiro</option>';
      usuarioSelect.disabled = true;
      return;
    }

    // Faz a requisição AJAX para buscar usuários do setor
    fetch('buscar_usuarios.php?setor_id=' + setorId)
      .then(response => response.json())
      .then(data => {
        usuarioSelect.disabled = false;
        usuarioSelect.innerHTML = '';

        if (data.length === 0) {
          usuarioSelect.innerHTML = '<option value="">Nenhum usuário encontrado</option>';
          usuarioSelect.disabled = true;
          return;
        }

        usuarioSelect.innerHTML = '<option value="">Selecione o usuário</option>';
        data.forEach(user => {
          const option = document.createElement('option');
          option.value = user.id;
          option.textContent = user.nome;
          usuarioSelect.appendChild(option);
        });
      })
      .catch(() => {
        usuarioSelect.innerHTML = '<option value="">Erro ao carregar usuários</option>';
        usuarioSelect.disabled = true;
      });
  });
});

function showRegister(show) {
  const reg = document.getElementById('registerBox');
  const login = document.getElementById('loginBox');
  if (show) {
    reg.style.display = 'block';
    login.style.display = 'none';
  } else {
    reg.style.display = 'none';
    login.style.display = 'block';
  }
}

window.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const showReg = urlParams.get('showReg');
  if (showReg === '1') {
    showRegister(true);
  }
});



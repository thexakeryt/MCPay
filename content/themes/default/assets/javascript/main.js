document.addEventListener("DOMContentLoaded", function(event) { 
  // popup function
  let product_name;
  const popup = document.querySelector('.popup');
  const popupClose = document.querySelector('.popup__close');
  const popupBackground = document.querySelector('.popup__background');
  const products = document.querySelectorAll('.products__product');

  popupClose.onclick = () => {
    popup.style.display = 'none';
  }

  popupBackground.onclick = () => {
    popup.style.display = 'none';
  }

  for(let i = 0; i < products.length; i++) {
    products[i].onclick = () => {
      productName = products[i].querySelector('.products__product-name').textContent;
      popup.querySelector('.popup__title strong').innerHTML = productName;
      popup.style.display = 'block';
    }
  }

  // filter function
  let filterName;
  let activeProducts;
  const filters = document.querySelectorAll('.filters__filter');
  for(let j = 0; j < filters.length; j++) {
    filters[j].onclick = () => {
      document.querySelector('.filters__filter.active').classList.remove('active');
      filters[j].classList.add('active');
      filterName = filters[j].textContent;
      activeProducts = document.querySelectorAll('.' + filterName);
      for(let p = 0; p < products.length; p++) {
        products[p].style.display = 'none';
      }
      for(let k = 0; k < activeProducts.length; k++) {
        activeProducts[k].style.display = 'flex';
      }
    }
  }

  // burger function
  const burger = document.querySelector('.burger');
  const header = document.querySelector('.header');

  burger.onclick = () => {
    if(!header.classList.contains('active')) {
      header.classList.add('active');
      burger.classList.add('active');
      document.querySelector('html').style.overflow = 'hidden';
      document.querySelector('.header .btn').style.display = 'block';
    } else {
      header.classList.remove('active')
      burger.classList.remove('active');
      document.querySelector('html').style.overflow = 'visible';
      document.querySelector('.header .btn').style.display = 'none';
    }
  }

  // button function
  const playButtons = document.querySelectorAll('.btn-begin');
  for(i = 0; i < playButtons.length; i++) {
    playButtons[i].onclick = (e) => {
      e.target.innerHTML = '';
      e.target.innerHTML = 'Ip Copied!';
      setTimeout(() => {
        e.target.innerHTML = '<i class="fa-solid fa-gamepad"></i>Begin to play';
      }, 500)
    }
  }

  // Info function
  const progress_bar = document.querySelector('.info__server-progress span');
  const online = document.querySelector('.info__server-players span').textContent;
  const max_online = document.querySelector('.info__server-progress p span').textContent;
  const progress =  (online * 100) / max_online;
  progress_bar.style.width = progress + '%';


  // Payment
  let item = document.querySelector('.popup__title strong').textContent;
  let nickname = document.querySelector('.popup__field input[name="nickname"]').value;
  const error = document.querySelector('strong.error');
  const submitBtn = document.querySelector('.popup__button');

  submitBtn.onclick = () => {
    submitBtn.setAttribute('disabled', 'disabled');
    item = document.querySelector('.popup__title strong').textContent;
    nickname = document.querySelector('.popup__field input[name="nickname"]').value;
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
      if (this.status == 200 && this.readyState == 4) {
        let responseSplit = this.responseText.split(':');
        if (responseSplit[0] == 'Error') {
          submitBtn.removeAttribute('disabled');
          error.textContent = this.responseText;
        } else {
          window.location.href = this.responseText;
        }
      }
    }
    xmlhttp.open("POST", "/catalog/components/Pay.php", true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send("item=" + item + "&nickname=" + nickname);
  }
});

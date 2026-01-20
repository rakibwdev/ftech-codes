// 	nav menu hover
document.addEventListener('DOMContentLoaded', function () {
  const menu = document.querySelector('.head-menue-content'); 
  const menuItems = document.querySelectorAll('.head-menue-item .menu-item');
  const contents  = document.querySelectorAll('.head-menue-content');
// 	  const contents = document.querySelectorAll('.sub-menu-content');
 
  menuItems.forEach((item, index) => {
// 		if (contents.length > 0) {
//     contents[0].classList.add('active');
//   }

    item.addEventListener('mouseenter', () => {
      contents.forEach(c => c.classList.remove('active'));
      if (contents[index]) {
        contents[index].classList.add('active');
      }
    });
  });

  menu.addEventListener('mouseleave', () => {
    contents.forEach(c => c.classList.remove('active'));
  });
});
	
// 	sub-menue-content
document.addEventListener('DOMContentLoaded', function () {
  const iconItems = document.querySelectorAll(
    '.sub-menu-item .elementor-icon-list-item'
  );
  const contents = document.querySelectorAll('.sub-menu-content');
 if (contents.length > 0) {
    contents[0].classList.add('active');
  }
  iconItems.forEach((item, index) => {
    item.addEventListener('mouseenter', () => {
      contents.forEach(c => c.classList.remove('active'));
      if (contents[index]) {
        contents[index].classList.add('active');
      }
    });
  });
});


// Ofcanvas Manue hover

document.addEventListener('DOMContentLoaded', function () {
    const offcanvasMenuItems = document.querySelectorAll('.ofcanva-menu .elementor-icon-list-items');
    const offcanvasMenu = document.querySelector('.ofcanva-menu .elementor-icon-list-items');
    const offcanvasContents = document.querySelectorAll('.ofcanva-menue-content');
console.log(offcanvasContents);
    offcanvasMenuItems.forEach((item, index) => {
        if (offcanvasContents.length > 0) {
            offcanvasContents[0].classList.add('active');
        }
        item.addEventListener('mouseenter', () => {
            offcanvasContents.forEach(c => c.classList.remove('active'));
            if (offcanvasContents[index]) {
                offcanvasContents[index].classList.add('active');
            }
        });
    });
    offcanvasMenu.addEventListener('mouseleave', () => {
        offcanvasContents.forEach(c => c.classList.remove('active'));
    });
});

<style>
.ofcanva-menue-content {
    display: none;
}   
.ofcanva-menue-content.active {
    display: block;


.e-off-canvas__content {
  transform: scaleX(0);
  transform-origin: center;
  opacity: 0;
  transition:
    transform 0.45s ease-in-out,
    opacity 0.3s ease-in-out;
}

.e-off-canvas__content[aria-hidden="false"] {
  transform: scaleX(1);
  opacity: 1;
}

</style>
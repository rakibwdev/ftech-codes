
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


{/* <style>
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

</style> */}


// Header $1200

// 	nav menu hover
const getAllHeadContent = document.querySelectorAll('.head-menue-content');
function removeHeadActive(){
    getAllHeadContent.forEach(item => item.setAttribute('show_status', false));
}
document.querySelectorAll('.head-menue-item .menu-item').forEach((item, index) => {
    item.addEventListener('mouseover', () => {
        removeHeadActive();
        getAllHeadContent[index].setAttribute('show_status', true);
    })
});
	getAllHeadContent.forEach(item => {
    item.addEventListener('mouseleave', () => {
        removeHeadActive();
    });
});
	
  // atribute for submenu
    // submenu 1
    const getAllSubContent = document.querySelectorAll('.sub-menu-content');
    function removeSubActive() {
        getAllSubContent.forEach(item => item.setAttribute('show_status', false));
    }
    document.querySelectorAll('.sub-menu-item .elementor-icon-list-item').forEach((item, index) => {
        item.addEventListener('mouseover', () => {
            removeSubActive();
            getAllSubContent[index].setAttribute('show_status', true);
        })
    });
    // submenu 2
    const getAllSubContent2 = document.querySelectorAll('.sub-menu-content2');
    function removeSubActive2() {
        getAllSubContent2.forEach(item => item.setAttribute('show_status', false));
    }
    document.querySelectorAll('.sub-menu-item2 .elementor-icon-list-item').forEach((item, index) => {
        item.addEventListener('mouseover', () => {
            removeSubActive2();
            getAllSubContent2[index].setAttribute('show_status', true);
        })
    });
    // submenu3
    const getAllSubContent3 = document.querySelectorAll('.sub-menu-content3');
    function removeSubActive3() {
        getAllSubContent3.forEach(item => item.setAttribute('show_status', false));
    }
    document.querySelectorAll('.sub-menu-item3 .elementor-icon-list-item').forEach((item, index) => {
        item.addEventListener('mouseover', () => {
            removeSubActive3();
            getAllSubContent3[index].setAttribute('show_status', true);
        })
    });


    // end submenu
// 	Offcanvas content animation
 // 	Offcanvas content animation
    document.addEventListener('DOMContentLoaded', () => {
        const offCanvas = document.querySelector('.e-off-canvas');
        const mobileOffCanvas = document.querySelector('#off-canvas-0c02358');
        const menuItems = document.querySelectorAll('.offcanvas_menu li');
        const subMenuContent = document.querySelectorAll('.content_item li');
        const acordianItems = document.querySelectorAll('.e-n-accordion-item');

        const resetElementStyles = (elements) => {
            elements.forEach(item => {
                item.style.transition = 'none';
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
            });
        };

        const animateElements = (elements) => {
            elements.forEach((item, index) => {
              	item.style.transition = 'opacity 0.9s ease, transform 0.9s ease';
                item.style.transitionDelay = `${index * 300}ms`;
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            });
        };

        // Reset all items initially
        resetElementStyles(menuItems);
        resetElementStyles(subMenuContent);
        resetElementStyles(acordianItems);

        // Observe off-canvas open/close changes
        const observeOffCanvas = (offCanvasElement) => {
            const observer = new MutationObserver(() => {
                const isOpen = offCanvasElement.getAttribute('aria-hidden') === 'false';
                if (isOpen) {
                    animateElements(menuItems);
                    animateElements(subMenuContent);

                    if (offCanvasElement.id === 'off-canvas-0c02358') {
                        animateElements(acordianItems); // Only for mobile
                    }
                } else {
                    resetElementStyles(menuItems);
                    resetElementStyles(subMenuContent);
                    if (offCanvasElement.id === 'off-canvas-0c02358') {
                        resetElementStyles(acordianItems);
                    }
                }
            });
            observer.observe(offCanvasElement, { attributes: true });
        };

        // Observe both desktop and mobile
        observeOffCanvas(offCanvas);
        observeOffCanvas(mobileOffCanvas);
    });
    // 	Offcanvas content animation end
// 	Offcanvas content animation end

// 	bottom button mobile
    document.addEventListener('DOMContentLoaded', () => {
        const button = document.querySelector('.button-group');
        if (!button) return;

        let lastScrollY = window.scrollY;

        window.addEventListener('scroll', () => {
            const currentScroll = window.scrollY;
            const pageHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent = (currentScroll / pageHeight) * 100;

            // Only work after 30% scroll
            if (scrollPercent < 30) {
                button.classList.remove('is-visible');
                button.classList.add('is-hidden');
                return;
            }

            if (currentScroll > lastScrollY) {

                button.classList.add('is-visible');
                button.classList.remove('is-hidden');
            }
            else {
                button.classList.remove('is-visible');
                button.classList.add('is-hidden');
            }

            lastScrollY = currentScroll;
        });
    });
	

let lastScrollTop = 0;

const header = document.querySelector('.dex_header');
const mobHeader = document.querySelector('.mob-men');

window.addEventListener('scroll', function () {

    let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    if (scrollTop > lastScrollTop) {

        if(header){
            header.style.transform = "translateY(-148%)";
            header.style.backgroundColor = "#00000070";

        }

        if(mobHeader){
            mobHeader.style.transform = "translateY(-100%)";
            mobHeader.style.backgroundColor = "#00000070";


        }

    } else {

        if(header){
            header.style.transform = "translateY(-48px)";
            header.style.backgroundColor = "#00000070";
        }

        if(mobHeader){
            mobHeader.style.transform = "translateY(0)";
            mobHeader.style.backgroundColor = "#00000070";
        }

    }

    lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;

});


// mega menu
const menuItems = document.querySelectorAll('.main_item-menu .elementor-nav-menu--main .menu-item');
const menuSubContent = document.querySelectorAll('.main_sub-menu');

menuItems.forEach((item, index) => {

    item.addEventListener('mouseenter', () => {
        menuSubContent.forEach((sub) => {
            sub.style.transform = 'translateY(30px)';
            sub.style.opacity = '0';
            sub.style.pointerEvents = 'none';
        });

        menuSubContent[index].style.transform = 'translateY(0)';
        menuSubContent[index].style.opacity = '1';
        menuSubContent[index].style.pointerEvents = 'auto';
    });

});

window.addEventListener('mouseover', (e) => {
    if (!e.target.closest('.main_item-menu') && !e.target.closest('.main_sub-menu')) {
        menuSubContent.forEach((sub) => {
            sub.style.transform = 'translateY(30px)';
            sub.style.opacity = '0';
            sub.style.pointerEvents = 'none';
        });
    }
});





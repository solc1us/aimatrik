(function ($) {
    "use strict";

///////////////////////////////////////////////////////

// Preloader
$(".preloader").delay(800).fadeOut("slow");

// Preloader End


// Menu

jQuery(document).ready(function () {
	jQuery('header .mainmenu').meanmenu({
    meanScreenWidth: "992",
  });
});


document.querySelectorAll('.menu-anim > li > a').forEach(button => button.innerHTML = '<div class="menu-text"><span>' + button.textContent.split('').join('</span><span>') + '</span></div>');

setTimeout(() => {
  var menu_text = document.querySelectorAll(".menu-text span");
  menu_text.forEach((item) => {
      var font_sizes = window.getComputedStyle(item, null);
      let font_size = font_sizes.getPropertyValue("font-size");
      let size_in_number = parseInt(font_size.replace("px", ""), 10);
      let new_size = parseInt(size_in_number / 3, 10);
      new_size = new_size + "px";
      if (item.innerHTML === " ") {
          item.style.width = new_size;
      }
  });
}, 1000);

// Menu End


// Search Start
document.addEventListener("click", (event) => {
  const searchToggle = event.target.closest(".search-icon");
  if (searchToggle) {
    searchToggle.classList.toggle("active");
  }
});
// Search End


///////////////////////////////////////////////////////
// Sticky Menu
$(window).on( 'scroll', function() {
    var scroll = $(window).scrollTop();
    if (scroll >= 150) {
        $(".menu-area").addClass("sticky");
    } else {
        $(".menu-area").removeClass("sticky");
    }
});
// Sticky Menu End


///////////////////////////////////////////////////////
// Magnific Popup gallery
$('.popup-gallery').magnificPopup({
    delegate: 'a', // child items selector, by clicking on it popup will open
    gallery: {
        enabled: true
    },
    type: 'image'
    // other options
});

$('.popup-youtube').magnificPopup({
  type: 'iframe'
});


// Magnific Popup gallery End


/*Trending Slide*/

var resourcesSlider = new Swiper('.resources-slider', {
  slidesPerView: 3,
  spaceBetween: 15,
  loop:true,
  speed: 1000,
  breakpoints: {
    320: {
      slidesPerView: 1
    },
    480: {
      slidesPerView: 1
    },
    768: {
      slidesPerView: 2
    },
    992: {
      slidesPerView: 2
    },
    1200: {
      slidesPerView: 3
    },
    1400: {
      slidesPerView:3
    }
  },
  navigation: {
    nextEl: ".resources-button-next",
    prevEl: ".resources-button-prev",
  },
});


/* Testimonial */

//top
var testimonial_top_slider = new Swiper('.testimonial-slide-top', {
  spaceBetween: 24,
  centeredSlides: true,
  speed: 8000,
  autoplay: {
    delay: 1,
  },
  loop: true,
  slidesPerView:'auto',
  allowTouchMove: false,
  disableOnInteraction: true
});

// Bottom
var testimonial_bottom_slider = new Swiper('.testimonial-slide-bottom', {
  spaceBetween: 24,
  centeredSlides: true,
  speed: 8000,
  autoplay: {
    delay: 1,
    reverseDirection: true
  },
  loop: true,
  slidesPerView:'auto',
  allowTouchMove: false,
  disableOnInteraction: true
});


// Testimonial Image Generator
var testimonialImg = new Swiper('.testimonial-img-slide', {
  fadeEffect: { crossFade: true },
  effect: 'fade',
  loop: true,
  allowTouchMove : false,
})

var testimonialInfo = new Swiper('.testimonial-info-slide', {
spaceBetween: 24,
slidesPerView: 1,
loop: true,
speed: 800,
allowTouchMove : false,
navigation: {
  nextEl: ".testimonial-info-button-next",
  prevEl: ".testimonial-info-button-prev",
},
pagination: {
  el: ".testimonial-info-pagination",
  clickable: true,
},
thumbs: {
  swiper: testimonialImg
}
});


var cg_testimonialSlider = new Swiper('.cg-testimonial-slide', {
  slidesPerView: 3,
  spaceBetween: 15,
  loop:true,
  speed: 1000,
  breakpoints: {
    320: {
      slidesPerView: 1
    },
    480: {
      slidesPerView: 1
    },
    768: {
      slidesPerView: 2
    },
    992: {
      slidesPerView: 2
    },
    1200: {
      slidesPerView: 3
    },
    1400: {
      slidesPerView:3
    }
  },
  navigation: {
    nextEl: ".cg-testimonial-info-button-next",
    prevEl: ".cg-testimonial-info-button-prev",
  },
  pagination: {
    el: ".cg-testimonial-info-pagination",
    clickable: true,
  },
  });

/* Testimonial End */


/* Brand */

var brand_slider = new Swiper('.brand-slide-wrap', {
  spaceBetween: 100,
  centeredSlides: true,
  speed: 5000,
  autoplay: {
    delay: 1,
  },
  loop: true,
  slidesPerView:'auto',
  allowTouchMove: false,
  disableOnInteraction: true,
  breakpoints: {
    320: {
      spaceBetween: 50,
    },
    992: {
      spaceBetween: 70,
    }
  },
});

/* Brand End */


///////////////////////////////////////////////////////
//Mixitup
$('.work-mixi').mixItUp();



///////////////////////////////////////////////////////
// Bottom to top start
$(document).ready(function () {
  $(window).on('scroll', (function () {
    if ($(this).scrollTop() > 100) {
      $('#scroll-top').fadeIn();
    } else {
      $('#scroll-top').fadeOut();
    }
  }));
  $('#scroll-top').on( 'click', function () {
    $("html, body").animate({ scrollTop: 0 }, 600);
    return false;
  });
});
// Bottom to top End



///////////////////////////////////////////////////////
// Odometer Counter
$(".counter-item").each(function () {
  $(this).isInViewport(function (status) {
    if (status === "entered") {
        for (var i = 0; i < document.querySelectorAll(".odometer").length; i++) {
        var el = document.querySelectorAll('.odometer')[i];
        el.innerHTML = el.getAttribute("data-odometer-final");
      }
    }
  });
});


window.onload = function () {

  // Custom Cursor
  const cursor = document.querySelector('.cursor');

  if (cursor) {
    const editCursor = e => {
      const { clientX: x, clientY: y } = e;
      cursor.style.left = x + 'px';
      cursor.style.top = y + 'px';
    };
    window.addEventListener('mousemove', editCursor);

    document.querySelectorAll("a, .cursor-pointer").forEach(item => {
      item.addEventListener('mouseover', () => {
        cursor.classList.add('cursor-active');
      });

      item.addEventListener('mouseout', () => {
        cursor.classList.remove('cursor-active');
      });
    });
  }
};

// Custom Cursor End


// Pricing Toggle

const tableWrapper = document.querySelector(".price_wrapper");
const switchInputs = document.querySelectorAll(".switch-wrapper input");
const prices = tableWrapper?.querySelectorAll(".price");
const toggleClass = "hide";

for (const switchInput of switchInputs) {
  switchInput.addEventListener("input", function () {
    for (const price of prices) {
      price.classList.add(toggleClass);
    }
    const activePrices = tableWrapper.querySelectorAll(
      `.price.${switchInput.id}`
    );
    for (const activePrice of activePrices) {
      activePrice.classList.remove(toggleClass);
    }
  });
}

// Pricing Toggle End


//Text Animation
if (typeof gsap !== "undefined" && typeof SplitText !== "undefined") {
  let splitTextLines = gsap.utils.toArray(".text-anim");

  splitTextLines.forEach(splitTextLine => {
    const tl = gsap.timeline({
      scrollTrigger: {
        trigger: splitTextLine,
        start: 'top 90%',
        duration: 2,
        end: 'bottom 60%',
        scrub: false,
        markers: false,
        toggleActions: 'play none none none'
      }
    });

    const itemSplitted = new SplitText(splitTextLine, { type: "lines" });
    gsap.set(splitTextLine, { perspective: 400 });
    itemSplitted.split({ type: "lines" })
    tl.from(itemSplitted.lines, { duration: .9, delay: 0.2, opacity: 0, rotationX: -80, force3D: true, transformOrigin: "top center -50", stagger: 0.1 });
  });
}



}(jQuery));


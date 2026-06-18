window.addEventListener("load", function () {

    fetch(msb_ajax.url + "?action=msb_get_posts")
        .then(res => res.json())
        .then(posts => {

            const titles = document.querySelectorAll(".news_title");
            const links  = document.querySelectorAll(".news_link");
            const times  = document.querySelectorAll(".news_time");
            const categorys = document.querySelectorAll(".news_category");


            posts.forEach((post, index) => {

                if (titles[index]) {
                    titles[index].innerHTML = post.title;
                }

                if (links[index]) {
                    links[index].setAttribute("href", post.link);
                }

                if (times[index]) {
                    times[index].innerHTML = timeAgo(post.time);
                }

                if (categorys[index]) {
                    categorys[index].innerHTML = post.category;
                }

            });

        });

    // optional time ago function
    function timeAgo(datetime) {
        const seconds = Math.floor((new Date() - new Date(datetime)) / 1000);

        const units = {
            year: 31536000,
            month: 2592000,
            day: 86400,
            hour: 3600,
            minute: 60
        };

        for (let key in units) {
            let val = Math.floor(seconds / units[key]);
            if (val >= 1) return "⏱ " + val + key[0].toUpperCase() + " AGO";
        }

        return "JUST NOW";
    }

    // MLB RUMORS SECTION
fetch(msb_ajax.url + "?action=msb_get_mlb_rumors")
    .then(res => res.json())
    .then(posts => {

        const titles   = document.querySelectorAll(".mlb_title");
        const links    = document.querySelectorAll(".mlb_link");
        const images   = document.querySelectorAll(".mlb_img img");
        const excerpts = document.querySelectorAll(".mlb_excerpt");
console.log( 'MLB RUMORS' ,posts);
        posts.forEach((post, index) => {

            if (titles[index]) {
                titles[index].innerHTML = post.title;
            }

            if (links[index]) {
                links[index].setAttribute("href", post.link);
            }

          if (images[index]) {
            images[index].src = post.image;
            images[index].srcset = ""; 
            images[index].sizes = "";
        }

            if (excerpts[index]) {
                excerpts[index].innerHTML = post.excerpt;
            }

        });

    });


// premium-analysis
fetch(msb_ajax.url + "?action=msb_get_premium_analysis")
    .then(res => res.json())
    .then(posts => {

        const titles   = document.querySelectorAll(".premium_title");
        const links    = document.querySelectorAll(".premium_link");
        const images   = document.querySelectorAll(".premium_img");
        const excerpts = document.querySelectorAll(".premium_excerpt");
console.log( 'PREMIUM ANALYSIS' ,posts);
        posts.forEach((post, index) => {

            if (titles[index]) {
                titles[index].innerHTML = post.title;
            }

            if (links[index]) {
                links[index].setAttribute("href", post.link);
            }

          if (images[index]) {
            images[index].style.backgroundImage = `url(${post.image})`;
            images[index].srcset = ""; 
            images[index].sizes = "";
        }

            if (excerpts[index]) {
                excerpts[index].innerHTML = post.excerpt;
            }

        });

    });

});
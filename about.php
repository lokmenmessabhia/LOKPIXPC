<?php
session_start();
include 'db_connect.php';
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Lokpix</title>
 <style>
    /* Modern General Styles */
    .about-page {
        margin: 0;
        padding: 0;
        font-family: 'Poppins', sans-serif;
        line-height: 1.8;
        color: #2d3436;
        background-color: #ffffff;
    }

    .about-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 60px 20px;
    }

    /* Modern About Section Styles */
    .about-section {
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        border-radius: 20px;
        margin: 20px;
        padding: 40px;
        position: relative;
        overflow: hidden;
    }

    .about-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(90deg, #00c6fb, #005bea);
    }

    .about-profile {
        text-align: center;
        margin-bottom: 50px;
        position: relative;
    }

    .about-profile img {
        width: 220px;
        height: 220px;
        border-radius: 30px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        transition: all 0.4s ease;
        object-fit: cover;
    }

    .about-profile img:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .about-text {
        padding: 0 20px;
    }

    .about-section h1 {
        color: #1a73e8;
        font-size: 3em;
        margin-bottom: 30px;
        text-align: center;
        font-weight: 700;
        letter-spacing: -1px;
    }

    .about-section h2 {
        color: #2d3436;
        font-size: 2em;
        margin-top: 40px;
        margin-bottom: 20px;
        font-weight: 600;
        position: relative;
        padding-left: 15px;
    }

    .about-section h2::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 25px;
        background: linear-gradient(180deg, #00c6fb, #005bea);
        border-radius: 2px;
    }

    .about-section p {
        margin-bottom: 25px;
        font-size: 1.1em;
        color: #636e72;
        line-height: 1.8;
    }

    .about-section ul {
        list-style-type: none;
        padding: 0;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin: 30px 0;
    }

    .about-section ul li {
        padding: 20px;
        background: rgba(255, 255, 255, 0.8);
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease;
        position: relative;
        padding-left: 50px;
    }

    .about-section ul li:hover {
        transform: translateY(-5px);
    }

    .about-section ul li:before {
        content: "→";
        color: #1a73e8;
        position: absolute;
        left: 20px;
        font-size: 1.2em;
        top: 50%;
        transform: translateY(-50%);
    }

    /* Modern Footer Styles */
    .about-footer {
        background: linear-gradient(145deg, #1a73e8, #0052cc);
        color: white;
        padding: 40px 0;
        text-align: center;
        margin-top: 60px;
        border-radius: 20px 20px 0 0;
    }

    .about-social-media {
        margin-top: 25px;
        display: flex;
        justify-content: center;
        gap: 30px;
    }

    .about-social-media a {
        display: flex;
        align-items: center;
        padding: 10px 20px;
        color: white;
        text-decoration: none;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .about-social-media a:hover {
        transform: translateY(-5px);
        background: rgba(255, 255, 255, 0.2);
    }

    .about-social-media img {
        width: 24px;
        height: 24px;
        margin-right: 10px;
        filter: brightness(0) invert(1);
    }

    .about-social-media span {
        font-weight: 500;
    }

    /* Modern Responsive Design */
    @media (max-width: 768px) {
        .about-container {
            padding: 30px 15px;
        }

        .about-section {
            padding: 30px 20px;
        }

        .about-section h1 {
            font-size: 2.2em;
        }

        .about-section h2 {
            font-size: 1.6em;
        }

        .about-profile img {
            width: 180px;
            height: 180px;
        }

        .about-text {
            padding: 0;
        }

        .about-social-media {
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
    }

    /* Add smooth scrolling */
    html {
        scroll-behavior: smooth;
    }

    /* Add animation for content loading */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .about-section {
        animation: fadeIn 0.8s ease-out forwards;
    }
    

    .about-profile {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 30px;
    padding: 60px 20px;
    
    
}


.about-developer {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 8px 25px rgba(0, 0, 128, 0.2);
    overflow: hidden;
    width: 340px;
    text-align: center;
    position: relative;
    transition: transform 0.4s ease, box-shadow 0.4s ease;
    border: 1px solid #d4e4ff;
}

.about-developer:hover {
    transform: scale(1.08) rotate(-1deg);
    box-shadow: 0 20px 50px rgba(0, 0, 128, 0.3);
}

.about-developer img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-bottom: 4px solid #0066ff;
    transition: transform 0.4s ease;
}

.about-developer:hover img {
    transform: scale(1.05);
}

.about-developer h3 {
    margin: 15px 0 5px;
    font-size: 1.8em;
    font-weight: 800;
    color: #0033cc;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.about-developer p {
    font-size: 1em;
    color: #0066ff;
    margin: 10px 0 20px;
    font-weight: 600;
}

.about-developer a {
    display: inline-block;
    margin: 8px 10px;
    padding: 12px 25px;
    border-radius: 50px;
    text-decoration: none;
    font-size: 0.9em;
    font-weight: bold;
    color: #ffffff;
    background: linear-gradient(135deg, #0033cc, #0066ff);
    transition: background 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 5px 15px rgba(0, 0, 128, 0.2);
}

.about-developer a:hover {
    background: linear-gradient(135deg, #0066ff, #0033cc);
    transform: translateY(-5px) scale(1.1);
    box-shadow: 0 10px 20px rgba(0, 0, 128, 0.3);
}

.about-developer .ribbon {
    position: absolute;
    top: 10px;
    left: -10px;
    background: linear-gradient(135deg, #0033cc, #0066ff);
    color: white;
    padding: 5px 20px;
    font-size: 0.8em;
    font-weight: bold;
    transform: rotate(-20deg);
    box-shadow: 0 5px 15px rgba(0, 0, 128, 0.2);
}

.about-developer .ribbon:before,
.about-developer .ribbon:after {
    content: '';
    position: absolute;
    top: 100%;
    border-style: solid;
    border-width: 0 10px 10px 0;
    border-color: transparent transparent #0033cc transparent;
    transform: rotate(45deg);
}

.about-developer .ribbon:after {
    left: 100%;
    border-width: 10px 10px 0 0;
    border-color: #0033cc transparent transparent transparent;
    transform: rotate(-45deg);
}

.back-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: linear-gradient(145deg, #3498db, #2980b9);
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    z-index: 1000;
    border: none;
}

.back-to-top.visible {
    opacity: 1;
    visibility: visible;
}

.back-to-top:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    background: linear-gradient(145deg, #2980b9, #3498db);
}

.back-to-top i {
    font-size: 20px;
    transition: transform 0.3s ease;
}

.back-to-top:hover i {
    transform: translateY(-2px);
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(52, 152, 219, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(52, 152, 219, 0);
    }
}

.back-to-top.visible {
    animation: pulse 2s infinite;
}

 </style>

</head>
<body>
    <div class="about-page">
        <main>
            <section class="about-section">
                <div class="about-container">
                <div class="about-profile">
    <div class="about-developer">
        <img src="https://b.top4top.io/p_3272e9f641.jpg" alt="Developer 1">
        <h3>LOKMANE MESSABHIA</h3>
        <p>Backend Developer</p>
        <a href="https://www.instagram.com/lokmen_messabhia" target="_blank">Instagram</a>
        
        <a href="mailto:lokmen16.messabhia@gmail.com">Email</a>
    </div>
    <div class="about-developer">
        <img src="https://j.top4top.io/p_3277n6dv61.jpg" alt="Developer 2">
        <h3>Saiffi Med Ali Zakaria</h3>
        <p>Frontend Developer</p>
        <a href="https://www.instagram.com/sf.zakaria__" target="_blank">Instagram</a>
        
        <a href="mailto:saiffizakaria56@gmail.com">Email</a>
    </div>
    <div class="about-developer">
        <img src="https://c.top4top.io/p_3273yb3z20.jpg" alt="Developer 3">
        <h3>Hammoudi   Wajdi</h3>
        <p>UI/UX Designer</p>
        <a href="https://www.instagram.com/wajdi2.0" target="_blank">Instagram</a>
      
        <a href="mailto:amine@example.com">Email</a>
    </div>
</div>

                    <div class="about-text">
                        <h1>About Lokpix</h1>
                        <p>Welcome to Lokpix, your number one source for all things related to computers and technology. We are dedicated to giving you the very best of computer hardware, software, and accessories, with a focus on quality, customer service, and uniqueness.</p>
                        
                        <h2>Our Story</h2>
                        <p>Founded in 2024, Lokpix has come a long way from its beginnings as a small local store. When we first started out, our passion for providing top-tier computer products at competitive prices drove us to do tons of research so that Lokpix can offer you the best products on the market. We now serve customers all over the region and are thrilled that we're able to turn our passion into our own website.</p>
                        
                        <h2>Our Mission</h2>
                        <p>Our mission is to make the latest and greatest in computing technology accessible to everyone. Whether you're a seasoned gamer, a professional, or a tech enthusiast, we have the right products and expertise to help you get the most out of your technology.</p>

                        <h2>Why Choose Lokpix?</h2>
                        <ul>
                            <li>Wide selection of top brands in computer hardware and accessories.</li>
                            <li>Expert customer support to help you make informed purchasing decisions.</li>
                            <li>Competitive pricing with regular discounts and promotions.</li>
                            <li>Fast and reliable shipping options.</li>
                        </ul>

                        <h2>Our Team</h2>
                        <p>Our team is made up of passionate tech enthusiasts who are always on the lookout for the latest trends in the industry. We believe in staying ahead of the curve and continuously improving our offerings to provide you with the best possible shopping experience.</p>

                        <p>We hope you enjoy our products as much as we enjoy offering them to you. If you have any questions or comments, please don't hesitate to contact us.</p>
                    </div>
                </div>
            </section>
         

        </main>

        
        <?php
include 'footer.php';
?>
        
    </div>
    <button class="back-to-top" aria-label="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        const backToTopButton = document.querySelector('.back-to-top');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        });

        backToTopButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>
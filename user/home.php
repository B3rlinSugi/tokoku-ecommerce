<?php ?>

<section id="hero-area" class="header-area header-eight">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 col-md-12 col-12">
                <div class="header-content">
                    <h1>Wujudkan pernikahanmu bersama Serenity.</h1>
                    <p>
                        Serenity hadir untuk menemani momen terindah Anda dengan sentuhan riasan yang lembut, anggun,
                        dan memancarkan ketenangan.
                        Kami percaya setiap pasangan memiliki pesonanya masing-masing. Karena itu, Serenity menghadirkan
                        make up pengantin pria dan wanita yang serasi, harmonis, dan tetap menonjolkan karakter alami
                        keduanya.
                    </p>
                    <div class="button">
                        <?php if ($isLoggedIn): ?>
                            <a href="#pricing" class="btn primary-btn">Mulai Daftar</a>
                        <?php else: ?>
                            <a href="#login-customer" class="btn primary-btn">Mulai Daftar</a>
                        <?php endif; ?>
                        <a href="https://www.youtube.com/watch?v=CGoxTMkNbmQ"
                           class="glightbox video-button">
                            <span class="btn icon-btn rounded-full">
                              <i class="lni lni-play"></i>
                            </span>
                            <span class="text">Lihat Intro</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 col-12">
                <div class="header-image">
                    <img src="assets/images/banner-header.jpg" alt="#"/>
                </div>
            </div>
        </div>
    </div>
</section>

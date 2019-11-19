<?php
$en_all_2738 = $this->config->item('en_all_2738'); //MENCH
?>
<div class="container">

    <div class="row" style="padding-top: 30px;">

        <div class="col-lg-3">&nbsp;</div>

        <div class="col-lg-2" style="text-align:left;">
            <img src="/img/mench-v2-128.png" class="mench-spin" />
        </div>

        <div class="col-lg-6">


            <h1>BLOGGING.<br />REINVENTED.</h1>


            <h2>MENCH IS...</h2>
            <ul class="decimal-list">
                <li>An interactive publishing platform for sharing stories & ideas that matter</li>
                <li>A conversational reading experience offered over the web or Messenger</li>
                <li>A learning game that rewards players every time they read or blog</li>
                <li class="learn_more hidden">An open-source protocol for building & sharing consensus</li>
                <li class="learn_more hidden">A non-profit organization on a mission to expand human potential</li>
            </ul>

            <div class="learn_more hidden">
                <h2>HOW TO <b class="play">PLAY</b></h2>
                <ul class="decimal-list">
                    <li>Earn a <?= $en_all_2738[6205]['m_icon'] ?> coin for every word you <b class="montserrat pink"><?= $en_all_2738[6205]['m_name'] ?></b></li>
                    <li>Earn a <?= $en_all_2738[4535]['m_icon'] ?> coin for every word you <b class="montserrat yellow"><?= $en_all_2738[4535]['m_name'] ?></b></li>
                    <li><b class="montserrat pink"><?= $en_all_2738[6205]['m_name'] ?></b> up to <?= config_var(11061) ?> words/month <b class="montserrat">FREE</b></li>
                    <li><b class="montserrat pink"><?= $en_all_2738[6205]['m_name'] ?></b> unlimited words for $<?= config_var(11162) ?>/month</li>
                    <li>Earn monthly cash with your <?= $en_all_2738[4535]['m_icon'] ?> coins</li>
                </ul>

                <p>Release Date is ~ <b class="montserrat">Early 2020</b></p>

            </div>

            <p>
                <a href="javascript:void(0);" onclick="$('.learn_more').toggleClass('hidden');" class="btn btn6205 montserrat learn_more">READ MORE <i class="fas fa-search-plus"></i></a>

            </p>


            <p>Or <a href="/players/signin" style="font-weight: bold; text-decoration: underline;">login</a> if you already have an account.</p>

        </div>

        <div class="col-lg-1">&nbsp;</div>
    </div>

</div>


<?php
$session_en = en_auth();
$en_all_2738 = $this->config->item('en_all_2738'); //MENCH
$en_all_11035 = $this->config->item('en_all_11035'); //MENCH PLAYER NAVIGATION
$en_all_10591 = $this->config->item('en_all_10591'); //PLAYER PLAYS

?>
<div class="container">

    <div class="row">
        <div class="col-lg">

            <h1>HOW TO PLAY</h1>
            <ul style="list-style: decimal;">
                <li>Earn 1x <?= $en_all_2738[4536]['m_icon'] ?><b class="montserrat play"><?= $en_all_2738[4536]['m_name'] ?></b> coin when you join</li>
                <li>Earn 1x <?= $en_all_2738[6205]['m_icon'] ?><b class="montserrat read"><?= $en_all_2738[6205]['m_name'] ?></b> coin for every word you read</li>
                <li>Earn 1x <?= $en_all_2738[4535]['m_icon'] ?><b class="montserrat blog"><?= $en_all_2738[4535]['m_name'] ?></b> coin for every word you blog</li>
                <li>Earn up to <?= config_var(11061) ?>x <?= $en_all_2738[6205]['m_icon'] ?><b class="montserrat read"><?= $en_all_2738[6205]['m_name'] ?></b> coins/month FREE</li>
                <li>Earn UNLIMITED <?= $en_all_2738[6205]['m_icon'] ?><b class="montserrat read"><?= $en_all_2738[6205]['m_name'] ?></b> coins for $5/month</li>
                <li>Earn monthly income with your <?= $en_all_2738[4535]['m_icon'] ?><b class="montserrat blog"><?= $en_all_2738[4535]['m_name'] ?></b> coins</li>
                <li>Earn more coins to unlock new <a href="/play/10957" class="btn btn-sm btn-play montserrat">SUPERPOWERS</a></li>
            </ul>

            <?php
            if ($session_en) {
                echo '<div style="padding-bottom:21px;"><a href="/play/'.$session_en['en_id'].'" class="btn btn-play montserrat">My Profile</a></div>';
            } else {
                echo '<div style="padding-bottom:21px;"><a href="/sign" class="btn btn-play montserrat">'.$en_all_11035[4269]['m_name'].'</a></div>';
            }
            ?>

        </div>
        <div class="col-lg">

            <h1 style="margin-bottom: 0;">TOP PLAYERS</h1>
            <?php
            echo '<table id="leaderboard" class="table table-sm table-striped">';
            echo '<thead>';
            echo '<tr style="padding:0;">';
            echo '<td style="width: 34%"><span class="parent-icon icon-block">'.$en_all_2738[4536]['m_icon'].'</span><b class="montserrat play">'.$en_all_2738[4536]['m_name'].'</b></td>';
            echo '<td style="width: 33%">'.$en_all_2738[6205]['m_icon'].' <b class="montserrat read">'.$en_all_2738[6205]['m_name'].'</b></td>';
            echo '<td style="width: 33%">'.$en_all_2738[4535]['m_icon'].' <b class="montserrat blog">'.$en_all_2738[4535]['m_name'].'</b></td>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody><tr><td colspan="3"><span class="icon-block"><i class="far fa-yin-yang fa-spin"></i></span></td></tr></tbody>';
            echo '</table>';
            ?>
        </div>
    </div>


    <script>

        $(document).ready(function () {
            load_leaderboard();
        });

        function load_leaderboard(){
            $.post("/play/leaderboard/", {}, function (data) {
                $('#leaderboard tbody').html(data);
                $('[data-toggle="tooltip"]').tooltip();
            });
        }

    </script>


</div>
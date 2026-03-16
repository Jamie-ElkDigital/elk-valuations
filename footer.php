  <footer style="
    background: #0d0d18;
    border-top: 1px solid rgba(197, 160, 89, 0.1);
    padding: 16px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-family: 'Open Sans', sans-serif;
    font-size: 11px;
    color: rgba(237,237,240,0.3);
    letter-spacing: 0.04em;
  ">
    <span>&copy; <?php echo date('Y'); ?> ELK Valuations (ELK Digital). All rights reserved.</span>
    <span>Design &amp; Development by <a href="https://elkdesignservices.com" target="_blank" style="color:rgba(237,237,240,0.5); text-decoration:none; border-bottom:1px solid rgba(197, 160, 89, 0.2);">ELK Digital</a> &mdash; elkdesignservices.com <span style="margin-left:8px; color:#ffffff; opacity:1.0;">
      <?php 
        $version = getenv('APP_VERSION') ?: '3.2.1';
        $commit = getenv('APP_COMMIT_SHA') ?: 'dev';
        $buildTime = getenv('BUILD_TIME') ?: date('j F Y', filemtime(__FILE__));
        echo "v{$version} [{$commit}] (Built: {$buildTime})"; 
      ?>
    </span></span>
  </footer>

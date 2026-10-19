<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salma & Mohamed - Our Love Story</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Cairo:wght@400;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- GSAP & Plugins -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Loading Screen -->
    <div id="loader" class="loader-container">
        <div class="loader-heart">
            <i class="fas fa-heart"></i>
        </div>
        <div class="loader-text">Loading Our Memories...</div>
    </div>

    <!-- Mouse Glow Effect -->
    <div class="mouse-glow"></div>

    <!-- Floating Music Player & Memories Counter -->
    <div class="floating-controls">
        <div class="music-toggle" id="music-toggle">
            <i class="fas fa-music"></i> Our Song
        </div>
        <!-- YouTube Player Container -->
        <div id="youtube-player" style="display:none;"></div>
    </div>

    <!-- YouTube IFrame API -->
    <script>
      var tag = document.createElement('script');
      tag.src = "https://www.youtube.com/iframe_api";
      var firstScriptTag = document.getElementsByTagName('script')[0];
      firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

      var ytPlayer;
      function onYouTubeIframeAPIReady() {
        ytPlayer = new YT.Player('youtube-player', {
          height: '0',
          width: '0',
          videoId: 'g7Zw5x6GKlE', // The requested video
          playerVars: {
            'playsinline': 1,
            'controls': 0,
            'loop': 1,
            'playlist': 'g7Zw5x6GKlE'
          }
        });
      }
    </script>

    <div class="particles-container" id="particles"></div>

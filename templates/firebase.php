<script>
  // Initialize Firebase
  var config = {
    apiKey: "<?php echo $apiKey ?>",
    authDomain: "<?php echo $projectId ?>.firebaseapp.com",
    databaseURL: "https://<?php echo $projectId ?>.firebaseio.com",
    projectId: "<?php echo $projectId ?>",
    storageBucket: "<?php echo $projectId ?>.appspot.com",
    messagingSenderId: "<?php echo $senderId ?>"
  };
  firebase.initializeApp(config);
</script>

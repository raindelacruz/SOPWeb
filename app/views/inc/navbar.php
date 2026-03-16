	<nav class="navbar navbar-expand-lg navbar-dark app-navbar mb-4">
	  <div class="container">
	  
		<a class="navbar-brand" href="<?php echo URLROOT; ?>/procedures">PDMS</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
		  <span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarNav">
		  <ul class="navbar-nav ml-auto">
			<?php if(isset($_SESSION['user_id'])) : ?>
				<li class="nav-item">
					<a class="nav-link" href="<?php echo URLROOT; ?>/procedures">Procedures</a>
				</li>
				<li class="nav-item">
                    <a class="nav-link" href="<?php echo URLROOT; ?>/users/profile">Manage Profile</a>
                </li>
			  <?php if(isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'super_admin'], true)) : ?>
				<li class="nav-item d-none d-md-block">
					<a class="nav-link" href="<?php echo URLROOT; ?>/procedures/create">PDMS Create</a>
				</li>
			  <?php endif; ?>
			  
			  
			  <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'super_admin') : ?>
				<li class="nav-item">
					<a class="nav-link" href="<?php echo URLROOT; ?>/users/manage">Manage Users</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?php echo URLROOT; ?>/activitylogs">Activity Logs</a>
				</li>
			  <?php endif; ?>
			  
			  <li class="nav-item">
					<a class="nav-link" href="<?php echo URLROOT; ?>/users/logout">Logout</a>
			  </li>
			<?php else: ?>
			  <li class="nav-item">
					<a class="nav-link" href="<?php echo URLROOT; ?>/users/login">Login</a>
			  </li>
			  <li class="nav-item">
					<a class="nav-link" href="<?php echo URLROOT; ?>/users/register">Register</a>
			  </li>
			<?php endif; ?>
		  </ul>
		</div>
	  </div>
	</nav>

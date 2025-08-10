<?php
// includes/footer.php
?>
    </div>
    
    <!-- Footer -->
    <footer class="mt-5 py-4 border-top border-secondary">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 text-muted">
                        &copy; <?php echo date('Y'); ?> Domain Checker. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                    <div class="d-flex justify-content-center justify-content-md-end gap-3">
                        <a href="#" class="text-muted text-decoration-none">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="#" class="text-muted text-decoration-none">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-muted text-decoration-none">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button type="button" class="btn btn-primary btn-floating btn-lg rounded-circle" id="btn-back-to-top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Back to top button
        const backToTopButton = document.getElementById("btn-back-to-top");
        
        window.onscroll = function() {
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                backToTopButton.style.display = "block";
            } else {
                backToTopButton.style.display = "none";
            }
        };
        
        backToTopButton.addEventListener("click", function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Check for success/error messages in URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('success')) {
                const message = urlParams.get('success');
                if (message) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            }
            
            if (urlParams.has('error')) {
                const message = urlParams.get('error');
                if (message) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 5000
                    });
                }
            }
        });
    </script>
</body>
</html>
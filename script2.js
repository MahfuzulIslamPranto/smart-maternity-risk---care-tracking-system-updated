// In your existing navigation code, add this:
const navButtons = document.querySelectorAll(".nav-btn");
const sections = document.querySelectorAll(".section");

navButtons.forEach(btn => {
    btn.addEventListener("click", function() {
        const targetId = this.getAttribute("data-target");
        
        // Update active button
        navButtons.forEach(b => b.classList.remove("active"));
        this.classList.add("active");
        
        // Show target section
        sections.forEach(sec => sec.classList.add("hidden"));
        document.getElementById(targetId).classList.remove("hidden");
        
        // Handle Mother Profile section
        if (targetId === 'motherProfile') {
            // If no mother is selected, show default state
            if (!currentProfileMotherId) {
                document.getElementById('noProfile').style.display = 'block';
                document.getElementById('profileLoading').style.display = 'none';
                document.getElementById('profileContent').style.display = 'none';
            }
        }
    });
});
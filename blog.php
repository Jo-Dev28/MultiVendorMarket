<?php
$page_title = 'Blog';
require_once 'includes/header.php';

// Sample blog posts (you can replace with database)
$posts = [
    ['title'=>'Top 10 Tech Gadgets for 2024','date'=>'Jan 15, 2024','excerpt'=>'Discover the latest tech gadgets...','image'=>'📱'],
    ['title'=>'How to Start Selling Online','date'=>'Jan 10, 2024','excerpt'=>'A beginner guide to e-commerce...','image'=>'🛍️'],
    ['title'=>'5 Tips for Safe Online Shopping','date'=>'Jan 5, 2024','excerpt'=>'Stay safe while shopping online...','image'=>'🔒'],
    ['title'=>'Why AI is Changing E-commerce','date'=>'Jan 1, 2024','excerpt'=>'Artificial Intelligence in retail...','image'=>'🤖'],
];
?>
<style>
.blog-hero{background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);padding:60px 0;border-radius:0 0 30px 30px;margin-bottom:40px;text-align:center}
.blog-hero h1{color:#fff;font-size:2.5rem;font-weight:800}
.blog-card{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);transition:all .3s;height:100%}
.blog-card:hover{transform:translateY(-5px);box-shadow:0 8px 30px rgba(0,0,0,0.12)}
.blog-card .image{height:180px;display:flex;align-items:center;justify-content:center;font-size:4rem;background:#eff6ff}
.blog-card .body{padding:20px}
.blog-card .title{font-size:1.1rem;font-weight:600;color:#1f2937}
.blog-card .date{color:#6b7280;font-size:.8rem}
.blog-card .excerpt{color:#6b7280;font-size:.9rem;margin:8px 0}
@media(max-width:768px){.blog-hero h1{font-size:1.8rem}}
</style>
<div class="blog-hero"><div class="container"><h1>Blog</h1><p>Latest news and articles from our marketplace.</p></div></div>
<div class="container mb-5">
    <div class="row g-4">
        <?php foreach($posts as $post): ?>
        <div class="col-md-6 col-lg-3">
            <div class="blog-card">
                <div class="image"><?= $post['image'] ?></div>
                <div class="body">
                    <div class="date"><?= $post['date'] ?></div>
                    <h5 class="title"><?= $post['title'] ?></h5>
                    <p class="excerpt"><?= $post['excerpt'] ?></p>
                    <a href="#" class="btn btn-sm btn-primary">Read More</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
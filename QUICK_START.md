# CMS Flipbook - Quick Start Guide

## 🚀 Launch Application

```bash
cd /Users/mac/Documents/Project/cms-flipbook/cms-flipbook
php artisan serve
```

Then open: **http://localhost:8000**

---

## 📚 Books Management

### View All Books
- Navigate to **Daftar Buku** in sidebar
- See grid layout with book covers
- Search by title/author: Use search form
- Filter by status: Use status dropdown (Draft/Published)

### Create New Book
1. Click **+ Tambah Buku** button
2. Fill in details:
   - Title (required)
   - Author
   - Publisher  
   - Description
3. Upload PDF file (required)
   - Automatically converted to page images
   - Full-page images for annotation
4. Click **Buat Buku**

### Edit Book Details
1. Click **Edit** button on book card
2. Update any field
3. Click **Simpan Perubahan**

### View Book Details
1. Click on book card (anywhere except Edit button)
2. See book info and page count
3. Click **Buka Pratinjau Penuh** for interactive flipbook preview
4. Click **Kelola Halaman** to manage pages in table view

### Publish/Unpublish Book
- Click **Terbitkan/Batalkan** button on book card
- Status changes immediately (Draft ↔ Published)

---

## 📄 Pages Management

### Access Pages
1. Click **Kelola Halaman** in sidebar, OR
2. Go to specific book and click **Kelola Halaman** button

### View Pages Table
- Searchable by book title
- Filterable by status
- Shows: Page #, Book, Thumbnail, Annotations, Audio, Status, Date
- 15 pages per page (pagination)

### Edit Page
1. Click **Edit** icon (pencil) in page row
2. Page loads in editor:
   - Left: Page image with annotation tools
   - Right: Annotation list and links to audio/details
3. Draw annotations (bounding boxes):
   - Click and drag on image to create rectangle
   - Enter label in prompted dialog
   - Rectangle appears on image and in list
4. To delete annotation: Click trash icon in annotation list
5. Changes auto-save

### Add Annotations
1. In **pages/edit** view
2. Click and drag on page image
3. Enter label when prompted
4. Annotation appears with orange border
5. Link audio to annotation via **Kelola Audio** button

### Delete Page
1. Click **Delete** icon (trash) in pages table
2. Confirm deletion
3. Page and all related annotations/audio are deleted

---

## 🎙️ Audio Management

### Access Audio Manager
1. From **pages/management** table: Click **Audio** icon for page
2. From **pages/edit**: Click **Kelola Audio** link
3. From **pages/show**: Click **Kelola Audio** button

### Add Audio
1. Select audio type:
   - **🎙️ Narasi** - Narration/voiceover
   - **🎵 Backsound** - Background music/sound effects
   - **📢 Audio Objek** - Audio linked to specific annotation
2. Enter label (e.g., "Intro Narration")
3. For object audio: Select which annotation to link
4. Upload audio file (MP3, WAV, OGG, M4A)
5. Click **Unggah Audio**

### Play Audio
- Use inline audio player controls
- Play/Pause/Volume/Download available

### Filter Audio
- Click tabs at top: **Semua** | **Narasi** | **Backsound** | **Audio Objek**
- Shows only selected audio type
- View linked annotation for object audio

### Delete Audio
- Click trash icon next to audio file
- Confirm deletion
- Audio file removed from page

### View Audio on Page
In **pages/show** view:
- Each annotation shows audio count badge (blue)
- In annotation list, see linked audio files
- Hover over annotation to see audio details

---

## 🎨 Annotations (Bounding Boxes)

### Create Annotation
In **pages/edit**:
1. Click and drag on page image to draw rectangle
2. Enter label name (e.g., "Bird", "House", "Cloud")
3. Annotation created with orange border
4. Appears in annotation list with coordinates and label

### View Annotation Details
In **pages/show**:
- See list of all annotations
- Shows: Label, Position (X, Y, W, H), Audio count
- Click annotation to see linked audio files

### Link Audio to Annotation
1. Create or go to page **pages/edit**
2. Select annotation
3. Go to **Kelola Audio** (pages/audio)
4. Select **Audio Objek** type
5. Choose annotation from dropdown
6. Upload audio file
7. Audio now linked to annotation

### Delete Annotation
In **pages/edit**:
1. Find annotation in list (right panel)
2. Click trash icon
3. Annotation removed from page and database

---

## 📋 Page Details View

Access via: **pages/show** (click page number in table or detail view)

Shows:
- Large page preview image (left)
- Page info panel (right):
  - Page number and ID
  - Total annotations count
  - Total audio files count
  - Created date
  - Links to Edit and Audio Management

---

## 🔍 Search & Filter

### Search Books
- From **Daftar Buku** page
- Type in search box: Searches by title/author/publisher
- Results update in real-time or on form submit

### Search Pages
- From **Kelola Halaman** page  
- Type in search box: Searches by book title
- Shows matching page rows

### Filter Books by Status
- From **Daftar Buku** page
- Select from dropdown: **Semua** | **Draft** | **Terbitkan**
- Books filtered immediately

### Filter Pages by Status
- From **Kelola Halaman** page
- Select from dropdown for status filtering
- Can combine with book search

---

## 👤 User Account

### View Profile
- Click user avatar (top-right) → Profile
- View and update account details

### Logout
- Click user avatar (top-right) → Logout
- Redirects to login page

---

## 💾 File Management

### PDF Storage
- Uploaded PDFs converted to WebP images (1 per page)
- Stored in: `storage/app/public/books/`
- Automatically cleaned up when book deleted

### Audio Storage
- Audio files stored in: `storage/app/public/audio/`
- Supported formats: MP3, WAV, OGG, M4A
- Max 10MB per file
- Automatically cleaned when audio deleted

### Image Storage
- Page images (WebP) in: `storage/app/public/books/`
- Can be referenced directly in views

---

## 🔧 Troubleshooting

### Page Shows Blank
- Check file permissions: `chmod -R 755 storage/`
- Clear cache: `php artisan cache:clear`
- Cache views: `php artisan view:cache`

### Audio Not Playing
- Verify file format (MP3/WAV/OGG/M4A)
- Check file was uploaded: `storage/app/public/audio/`
- Check browser supports HTML5 audio

### PDF Not Converting
- Verify Imagick installed: `php -r "echo extension_loaded('imagick') ? 'YES' : 'NO';"`
- Check file is valid PDF
- Check file permissions in storage folder

### Routes Returning 404
- Clear route cache: `php artisan route:cache` 
- Clear config cache: `php artisan config:clear`

### Database Errors
- Run migrations: `php artisan migrate`
- Check database connection in `.env`
- Verify database exists and is accessible

---

## 📞 Command Reference

```bash
# Start development server
php artisan serve

# Run migrations  
php artisan migrate

# Clear all caches
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Run tests
php artisan test

# Generate key
php artisan key:generate

# Database reset (⚠️ Warning: deletes all data)
php artisan migrate:reset
php artisan migrate:refresh

# Tinker (interactive shell)
php artisan tinker
```

---

## ✅ Application is Ready!

Your CMS Flipbook is fully functional and ready to use.

**Start now**: `php artisan serve` → http://localhost:8000

Enjoy! 🎉

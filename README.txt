==============================================
  ADMISSION DASHBOARD  -  SETUP (XAMPP)
==============================================

1. Copy this whole folder to:      C:\xampp\htdocs\admissionform
2. Start Apache and MySQL in the XAMPP control panel.
3. Open http://localhost/phpmyadmin
      - click New, create a database named:   admissionform
      - click that database -> Import -> choose admissionform.sql -> Go
4. (One time) open  http://localhost/admissionform/reset-admin.php
      Admin login becomes:   admin@example.com  /  Admin@123
      The file deletes itself after it runs.
5. Student side :  http://localhost/admissionform/
   Admin side   :  http://localhost/admissionform/admin-signin.php


FILES - WHAT EACH ONE DOES
--------------------------
Shared
  db.php            connects to the database + the 3 courses list (CS, ENTC, AIDS)
  session.php       starts the session
  auth.php          blocks a page if the STUDENT is not logged in
  admin-auth.php    blocks a page if the ADMIN is not logged in
  header.php        student top bar          footer.php   bottom bar
  admin-header.php  admin top bar
  style.css         all design                validation.js  small JavaScript

Student side
  registration.php  create account            signin.php     login
  index.php         Admission Dashboard (3 cards + Submit button)
  personal.php      personal / family / address form
  academic.php      SSC, HSC/Diploma and the 3 course choice
  documents.php     upload + view the 6 documents
  submit.php        final submit (creates application number)
  profile.php       view all details          aboutus.php    about page
  logout.php

Admin side (login only - no admin registration)
  admin-signin.php          admin login
  admin-dashboard.php       totals + CS / ENTC / AIDS cards + latest applications
  admin-students.php        student list, filter by course / status, search
  admin-student-view.php    one student's full details + documents
  admin-logout.php
  reset-admin.php           run once to set the admin password

uploads/  -> img (photos) and pdf (other documents) are saved here.

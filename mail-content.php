<?php
// Function to generate the email content
function generateVenueEmail($venue_details, $customer_name, $location, $variation_name, $instructions_pram, $product_name, $product_id) {
    $time = "10:00 am - 5:00 pm"; // Common time for all sessions
    $instructions = "instructions" . $instructions_pram;
    $nail_technician = null;

    if ($product_id == 394220) {
        $nail_technician = "<style>
    ul {
        list-style-type: disc;
    }
</style><p><span style='background-color: rgb(229, 241, 143);'><strong>Your Training Day’s Instructions:</strong></span><span></span></p>
<ul>
  <li><span>Wear comfortable clothing, especially for the pedicure session, and have naked/bare nails.</span></li>
  <li><span>We will not be able to remove any extensions or colour from your nails during or after the training; therefore, please arrive with bare nails.</span></li>
  <li><span>We will cover the removal procedure during the course. Please purchase acetone (available at pharmacies or online) so that you can remove your extensions or colour at home.</span></li>
  <li><span>Kindly bring your own lunch, notebook, and pen on the training date.</span></li>
</ul>
<br/>
<p><span><strong>Here is the curriculum for your 3 days of Nail technician Training</strong></span></p>
<p><span style='background-color: rgb(229, 241, 143);'><strong>Day 1:</strong></span><span><strong> </strong></span></p>
<p><span><strong>Manicure (10 am-1 pm)</strong></span></p>
<ul>
  <li><span>Nail technology career prospects</span></li>
  <li><span>Client discussions and insurance prerequisites</span></li>
  <li>
    <span>Health &amp; Safety</span>
    <ul>
      <li><span>Sterilization</span></li>
      <li><span>Sanitization</span></li>
      <li><span>Understanding of COSHH (Control of Substances Hazardous to Health) regulations</span></li>
    </ul>
  </li>
  <li>
    <span>Structure and function of nails, and GDPR</span>
    <ul>
      <li><span>Basic anatomy and physiology of nails, hands, and feet</span></li>
      <li><span>Understanding common nail disorders and contraindications</span></li>
      <li><span>Equip technicians to recognize healthy vs. unhealthy nails</span></li>
    </ul>
  </li>
  <li><span>Different manicure types, treatment durations, and pricing</span></li>
  <li>
    <span>Step-by-step guide to performing a professional manicure</span>
    <ul>
      <li><span>Cuticle care</span></li>
      <li><span>Filing</span></li>
      <li><span>Hand massage</span></li>
    </ul>
  </li>
  <li><span>Contraindications</span></li>
</ul>
<p><span> <strong>Pedicure (2 pm-5 pm)</strong></span></p>
<ul>
  <li><span>Advantages of getting a pedicure</span></li>
  <li><span>Tools and preparation</span></li>
  <li><span>Feet-related conditions that prevent treatment</span></li>
  <li><span>Practical demonstration of methods (including filing and cuticle care)</span></li>
  <li><span>Hands-on practice with fellow students (please come with bare nails)</span></li>
  <li><span>Question and answer session</span></li>
</ul>
<p><span style='background-color: rgb(229, 241, 143);'><strong>Day 2:</strong></span><span><strong> </strong></span></p>
<p><span><strong>Gel Polish (10 am-1 pm)</strong></span></p>
<ul>
  <li><span>Explanation of gel polish and various brands</span></li>
  <li><span>Advantages of using gel polish</span></li>
  <li><span>Resolving issues with gel polish</span></li>
  <li><span>Practical demonstration of techniques (including the application of colour and removal)</span></li>
  <li><span>Hands-on practice session for students (attendance with bare nails required)</span></li>
  <li><span>Question and answer session</span></li>
</ul>
<p><span><strong>Gel In Bottle - Live Practice &amp; Observation (2 pm-5 pm)</strong></span></p>
<ul>
  <li><span>What is BIAB and How to Apply Builder Gel</span></li>
  <li><span>Difference between BIAB’s</span></li>
  <li><span>Tip Application and Repairs</span></li>
  <li><span>Nail Prep, Cuticle Work and Nail Shapes</span></li>
  <li><span>Filing Techniques and Correct Product Ratio</span></li>
  <li><span>Removal of Product</span></li>
  <li><span>Aftercare and Product Advice</span></li>
</ul>
<p><span style='background-color: rgb(229, 241, 143);'><strong>Day 3:</strong></span><span><strong> </strong></span></p>
<p><span><strong>UV Gel (10 am-1 pm)</strong></span></p>
<ul>
  <li><span>Nail Shapes &amp; Structure: Introduction to common nail shapes (square, almond, stiletto, etc.), selecting shapes to suit clients, and understanding the importance of the apex and stress zones in nail extensions.</span></li>
  <li><span>Cuticle Preparation with E-File: Safe use of the e-file for cuticle lifting and removal, choosing appropriate bits, and techniques for preparing the cuticle area without damaging the natural nail.</span></li>
  <li><span>Form &amp; Tip Extensions: Application techniques for both forms and tips, including proper alignment, blending tips with the natural nail, and achieving a secure fit for a smooth, seamless extension.</span></li>
  <li><span>Comparison of Building Materials: Contrasts between UV gel and Polygel, covering characteristics, application techniques, curing mechanisms, and selection criteria aligned with client preferences. All evaluations will be communicated verbally (not demonstrated), except UV gel’s light-based curing process, which will be referenced as a practical distinction.</span></li>
  <li><span>Filing &amp; Shaping: Correct filing techniques and grit selection, refining nail shapes, and creating a smooth, symmetrical finish while maintaining nail structure.</span></li>
  <li><span>Finishing Touches &amp; Client Aftercare: Final cuticle care, buffing, and polishing techniques, plus educating clients on aftercare routines, like using cuticle oil and maintaining nails to prevent lifting.</span></li>
</ul>
<p><span><strong>Working With E-File (2 pm-5 pm)</strong></span></p>
<ul>
  <li><span>Overview of the E-file machine</span></li>
  <li><span>Handling techniques</span></li>
  <li>
    <span>Explanation of different drill bits</span>
    <ul>
      <li><span>Coarse Bits</span></li>
      <li><span>Medium/Fine Bits</span></li>
      <li><span>Cuticle Bits</span></li>
    </ul>
  </li>
  <li><span>Hands-on practice</span></li>
  <li><span>Tools, manufacturers, time, and cost</span></li>
  <li><span>Demonstration and hands-on practice of tip application (including infills and removals)</span></li>
  <li><span>Problem-solving</span></li>
  <li><span>Question and answer session</span></li>
</ul>";
    }

    // Start email content
    $mail_content = "
    <p>Dear $customer_name,</p>
    <p>Good day!<br>
    Please find the address and time below for the practical session on $product_name.</p>
    <p><strong>Date: $variation_name<br>
    Time: $time</strong></p>
    <p><strong>Venue Address:<br>
    {$venue_details['address']}</strong></p>
    ";
    
    // Add venue instructions and parking details
    $mail_content .= "{$venue_details['instructions_all']}";
    $mail_content .= "
    <p>If you are arriving by <strong>Train or Bus</strong>, our venue is easily accessible via public transportation. You can check detailed directions by {$venue_details['vanue_link']}.</p>
    <p><strong>Parking:</strong> {$venue_details['parking']}</p>
    ";

    // Add any specific instructions if available
    if (isset($venue_details[$instructions])) {
        $mail_content .= "<strong>{$venue_details[$instructions]}</strong>";
    }

    // Add important note about busy phone lines
    $mail_content .= "
    <p><strong>Please note!!!:</strong> On the training day during the morning, our telephone lines will be extremely busy, and we will not have the facility to give you telephone directions to the venue.</p>
    <p>You are requested to come to the training venue by <strong>9:45 am</strong>. After <strong>10 am</strong>, we will not allow you to the classroom, as it may interrupt the class.</p>
    <p><strong>Photo ID:</strong> Please bring your photo ID, such as a driving license/passport, when you attend the practical session.</p>
    ";

    if ($product_id == '421612') {
      $mail_content .= "Please wear comfortable clothing as you will be giving and receiving a full body massage during the practical part of the course. Let us know if you have any medical conditions that might contraindicate the treatment.<br/>

      It is essential that all students participate fully in both giving and receiving the treatment to ensure a comprehensive learning experience for everyone.<br/>
      
      Please bring a pen, water bottle, and a packed lunch or snack for the lunch break.";
    }

    if ($product_id == '414746' || $product_id == '414748' || $product_id == '414685') {
      $mail_content .= "Your booking is non-refundable. This means if you do miss your course, you will be very welcome to attend another course, but you will need to pay again. You can, however, postpone your booking for a later date; you must inform us 3 working days before the practical session via email at <a href='mailto:info@lead-academy.org'>info@lead-academy.org</a>. You can only do this once per booking.</br></br>
      
      Students will be required to work on each other during the session, so please inform us in advance if you have any restrictions due to religious beliefs or recent surgery.  </br></br>

      Wear comfortable clothing, keep nails short and hair tied back, avoid wearing jewelry, bring cleansing wipes, and minimize makeup to avoid unnecessary delays.  </br></br>

      While previous courses may cover similar areas, it is essential to maintain attention and consideration for others. Negative comments or comparisons to other teachings are not helpful or necessary.
      ";

    }

    // Add nail technician specific instructions if available
    if (isset($nail_technician)) {
        $mail_content .= $nail_technician;
    }

    // End email content
    $mail_content .= "
    <p><strong>You are requested to bring your lunch, notebook & pen on the training date.</strong></p>
    <p>Kind regards,<br>Lead Academy</p>
    ";

    return $mail_content;
}

// Define venue details for all locations
$venues = [
    "London" => [
        "vanue_link" => "<a href=\"https://lead-academy.org/venue/london\">clicking here</a>",
        "address" => "2nd Floor, Unit: 2.3, Bank Studio<br>23 Park Royal Road<br>London NW10 7JH",
        "instructions_all" => "<strong>Instructions to get into the training room:</strong> Upon arriving at the venue Bank Studio, use the intercom to dial 203 to access the building. Take the lift to the 2nd floor and go to unit 2.3.",
        "parking" => "No on-street parking is available. Alternative parking arrangements can be found by <a href=\"https://www.yourparkingspace.co.uk/search?rental=short&lat=51.526108&lng=-0.266143&address=NW10%207JH,%20London\">clicking here</a>.It is advised to book your parking space 2 days before your class date to get the parking nearby at a reasonable price.",
    ],
    "Swindon" => [
        "vanue_link" => "<a href=\"https://lead-academy.org/venue/swindon\">clicking here</a>",
        "address" => "Office 15, Pure Offices<br>Kembrey Park<br>Swindon, SN2 8BW",
        "instructions_all" => "<p><strong>Instructions to get into the training venue:</strong><br></p>",
        "parking" => "Free on-site parking is available on a first-come, first-serve basis. Alternative parking arrangements can be found by <a href=\"https://www.yourparkingspace.co.uk/search?rental=short&lat=51.578885&lng=-1.766471&address=SN2%208BW\">clicking here</a>.",
    ],
    "Bristol" => [
        "vanue_link" => "<a href=\"https://lead-academy.org/venue/bristol\">clicking here</a>",
        "address" => "Filwood Community Centre,<br>15 – 19 Filwood Broadway,<br>Bristol,<br>BS4 1JL",
        "instructions_all" => "<p><strong>Instructions to get into the training venue:</strong><br></p>",
        "parking" => "Free on-street parking is available on a first-come, first-serve basis. Alternative parking arrangements can be found by <a href=\"https://www.yourparkingspace.co.uk/search?rental=short&lat=51.425192&lng=-2.5879&address=BS4%201JP\">clicking here</a>.",
    ],
    "Cardiff" => [
        "vanue_link" => "<a href=\"https://lead-academy.org/venue/cardiff\">clicking here</a>",
        "address" => "Ynysmaerdy Community Centre<br>Glan-Yr-Ely,<br>Ynysmaerdy,<br>Pontyclun,<br>CF72 8LJ",
        "instructions_all" => "<p><strong>Instructions to get into the training venue:</strong><br></p>",
        "parking" => "Free on-site parking is available on a first-come, first-serve basis.",
    ],
    "Birmingham" => [
        "vanue_link" => "<a href=\"https://lead-academy.org/venue/birmingham\">clicking here</a>",
        "address" => "Lead Academy,<br>Office 4J/4K, 4th Floor<br>Cobalt Square<br>83-85 Hagley Road<br>Birmingham<br>B16 8QG",
        "instructions_all" => "<p><strong>Instructions to get into the training venue:</strong><br></p>",
        "instructions" => "👉 If your class is during a weekday and if you are unable to find the venue, please watch this tutorial for instruction:<br>https://www.youtube.com/watch?v=WF_LhJM3i-M<br><br>👉 If your class is during a weekend and if you are unable to find the venue, please watch this tutorial for instruction:<br>https://www.youtube.com/watch?v=OgLPH8dXLRk",
        "instructions_weekday" => "👉 If your class is during a weekday and if you are unable to find the venue, please watch this tutorial for instruction:<br>https://www.youtube.com/watch?v=WF_LhJM3i-M",
        "instructions_weekend" => "👉 If your class is during a weekend and if you are unable to find the venue, please watch this tutorial for instruction:<br>https://www.youtube.com/watch?v=OgLPH8dXLRk",
        "parking" => "Free on-site limited visitor parking is available on a first-come, first-serve basis. Alternative paid parking arrangements can be found by <a href=\"https://www.yourparkingspace.co.uk/search?rental=short&address=B16%208QG,%20Birmingham&lat=52.472511&lng=-1.922624&include_booked=false&space_size&season_plan=mon-sun&sort=location&start=2024-10-02T16%3A30%3A00%2B00%3A00&end=2024-10-02T23%3A30%3A00%2B01%3A00\">clicking here</a>.",
    ],
];

$nail_subject = "⚠️ URGENT: Need to Bring Few Essential Materials for Upcoming Nail Technician Training";
$nail_message = "<p>Dear learner,</p>
<p>Good day. Hope you are doing well. As you have upcoming Nail Technician training therefore it’s required to bring a few materials from home for that training. Please note: if you wish to start your career as a Nail Technician, you are required to buy the mentioned kits of PDF attachment to practice at home/salon. Therefore, please do not forget to bring those materials along with you during the training day. The rest of the training materials will be provided by us during the training.</p>
<p>We suggest a few <a href='https://lead-academy.org/wp-content/uploads/0223/12/Materials-and-Supplier-list.pdf'>suppliers</a> below as they are very good and professional. Please make sure to place your orders as soon as possible, as delivery times may vary. We have attached the PDF for Kits list and recommended Supplier details.</p>
<p>If you require any further information please do not hesitate to get back in touch.</p>
<p>Regards,<br/>
Lead Academy Support Team</p>";
$nail_time = date('Y-m-d H:i:s', strtotime('+24 hours'));
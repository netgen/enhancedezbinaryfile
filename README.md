enhancedezbinaryfile
====================

[![Downloads](https://img.shields.io/packagist/dt/netgen/enhancedezbinaryfile.svg?style=flat-square)](https://packagist.org/packages/netgen/enhancedezbinaryfile/stats)
[![Latest stable](https://img.shields.io/packagist/v/netgen/enhancedezbinaryfile.svg?style=flat-square)](https://packagist.org/packages/netgen/enhancedezbinaryfile)
[![License](https://img.shields.io/github/license/netgen/enhancedezbinaryfile.svg?style=flat-square)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg?style=flat-square)](https://secure.php.net/)

The Enhanced eZBinaryfile datatype makes it possible to use the information collector functionality to allow (anonymous) users to send/upload files as attachments.  If mail is enabled for the information collector, the file is sent as a multipart MIME attachment./*
    Enhanced eZBinaryfile for eZ publish 4.0+
    Developed by Steven E. Bailey and Sebastiaan van der Vliet
    Leiden Tech, Leiden the Netherlands
    
    http://www.leidentech.com, info@leidentech.com
    

    This file may be distributed and/or modified under the terms of the
    GNU General Public License" version 2 as published by the Free
    Software Foundation and appearing in the file LICENSE.GPL included in
    the packaging of this file.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    The "GNU General Public License" (GPL) is available at
    http://www.gnu.org/copyleft/gpl.html.
*/


Enhanced eZBinaryfile Datatype: Version: 1.0
-------------------------------------

1. Context
----------
This datatype was developed to make it possible to send binary files collected from forms as multipart MIME emails.

2. Features
-----------
The Enhanced eZBinaryfile datatype makes it possible to use the information collector functionality to allow anonymous users to send files as attachments.

3. Example of use
-----------------
Enhanced eZBinaryfile can be used to allow people to attach documents to
contact forms.

4. Known bugs, limitations, etc.
-----------------------------
The datatype has the following limitations and bugs:

- Even with restrictions, it remains a potential security risk to allow
  anonymous users to upload binary files to the server.
- If the file is valid but the form fails for another reason the file is still      uploaded to the storage directory.
- When the collectedinfo is removed from the backend, the file is not removed.
- File types are only validated by the filename which is potentially dangerous.
- This makes use of the parsexml template operator (which is included) in the
  autoloads directory.  In the event that this template operator is already
  being loaded as an extension, then the autoload should be removed.
- Not tested with a cluster.  Shouldn't need cluster functionality anyway with
  the way it is set up now.
  
5. Feedback
--------------------------------
Please send all your remarks, comments and suggestions for improvement to
info@leidentech.com.


6. Disclaimer
-------------------------
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

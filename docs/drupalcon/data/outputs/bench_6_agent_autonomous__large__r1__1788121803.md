# Drupal

*From Wikipedia, the free encyclopedia*

Web content management system

| Drupal | |
|---|---|
|  | |
| **Original author** | Dries Buytaert |
| **Developer** | Drupal community |
| **Release** | January 15, 2001; 25 years ago (2001-01-15)[1] |
| **Stable release** | 11.4.1[2] ✎ / 3 July 2026; 57 days ago |
| **Written in** | PHP, using Symfony |
| **Operating system** | Unix-like, Windows |
| **Platform** | Web platform |
| **Size** | 100 MB |
| **Type** | Content management framework<br>Content management system<br>Blog software<br>Open source<br>Knowledge management |
| **License** | GPL-2.0-or-later[3] |
| **Website** | drupal.org |
| **Repository** | Drupal Repository |

&gt; 
&gt; This article **relies excessively on references to primary sources**. Please improve this article by adding secondary or tertiary sources.
&gt; *Find sources:* "Drupal" – news **·** newspapers **·** books **·** scholar **·** JSTOR *(October 2022)* *(Learn how and when to remove this message)*

**Drupal** (/ˈdruːpəl/)[4] is a free and open-source web content management system (CMS) written in PHP and distributed under the GNU General Public License.[3][5][6] Drupal provides an open-source back-end framework for at least 14% of the top 10,000 websites worldwide[7] and 1.2% of the top 10 million websites[8]—ranging from personal blogs to corporate, political, and government sites.[9] Drupal can also be used for knowledge management and for business collaboration.[10]

As of March 2022, the Drupal community had more than 1.39 million members,[11][12][13] including 124,000 users actively contributing,[14] resulting in more than 50,000 free modules that extend and customize Drupal functionality,[15] over 3,000 free themes that change the look and feel of Drupal,[16] and at least 1,400 free distributions that allow users to quickly and easily set up a complex, use-specific Drupal in fewer steps.[17]

The base of Drupal is known as **Drupal core**, which contains basic features common to content-management systems. These include user account registration and maintenance, menu management, RSS feeds, taxonomy, page layout customization, and system administration. The Drupal core installation can serve as a simple website, a single or multi-user blog, an Internet forum, or a community website providing for user-generated content.

Drupal also describes itself as a web application framework.[18] When compared with notable frameworks, Drupal meets most of the generally accepted feature requirements for such web frameworks.[19][20]

Although Drupal offers a sophisticated API for developers, basic Web-site installation and administration of the framework require no programming skills.[21]

Drupal runs on any computing platform that supports both a web server capable of running PHP and a database to store content and configuration.

In 2023/2024, Drupal received over 250,000 Euros from Germany's Sovereign Tech Fund.[22][23]

Drupal is officially recognized[24] as a Digital Public Good.

## Contents

1. History
2. Drupal Core
   * 2.1 Core modules
   * 2.2 Core themes
   * 2.3 Drupal CMS
   * 2.4 Localization
   * 2.5 Auto-update notification
   * 2.6 Database abstraction
   * 2.7 Windows development
   * 2.8 Accessibility
3. Extending the core
   * 3.1 Modules
   * 3.2 Themes
   * 3.3 Distributions
4. Architecture
5. Community
   * 5.1 Notable users
6. Security
7. See also
8. References
9. Further reading
10. External links

## History

**Latest major and supported releases[25]**

| Version | Release date |
|---|---|
| Latest version: 11.4.1 | 3 July 2026[2] |
| Supported: 10.6.2 | 8 January 2026[26] |
| Unsupported: 7.103 | 4 December 2024[27] |
| Unsupported: 9.5.11 | 20 September 2023[28] |
| Unsupported: 8.9.20 | 17 December 2021 [29] |
| Unsupported: 6.38 | 24 February 2016[30] |
| Unsupported: 5.23 | 11 August 2010[31] |
| Unsupported: 4.7.11 | 10 January 2008[32] |
| Unsupported: 3.0 | 15 September 2001[33] |
| Unsupported: 2.0 | 15 March 2001[34] |
| Unsupported: 1.0 | 15 January 2001[35] |
| Legend: Unsupported · Supported · **Latest version** · Preview version · Future version | |

Drupal was originally written by Dries Buytaert as a message board for his friends to communicate in their dorms while working on his Master's degree at the University of Antwerp.[36][37] After graduation, Buytaert moved the site to the public internet and named it Drop.org.[36] Between 2003 and 2008, Buytaert worked towards a PhD degree at Ghent University.[38]

The name *Drupal* represents an English rendering of the Dutch word *druppel*, which means "drop" (as in a water droplet).[39] The name came from the now-defunct Drop.org, whose code slowly evolved into Drupal. Buytaert wanted to call the site "dorp" (Dutch for "village") for its community aspects, but mistyped it when checking the domain name and thought the error sounded better.[40]

Drupal became an open source project in 2001.[40] Interest in Drupal got a significant boost in 2003 when it helped build "DeanSpace" for Howard Dean, one of the candidates in the U.S. Democratic Party)'s primary campaign for the 2004 U.S. presidential election. DeanSpace used open-source sharing of Drupal to support a decentralized network of approximately 50 disparate, unofficial pro-Dean websites that allowed users to communicate directly with one another as well as with the campaign.[41] After Dean ended his campaign, members of his Web team continued to pursue their interest in developing a Web platform that could aid political activism by launching CivicSpace Labs in July 2004, "...the first company with full-time employees that was developing and distributing Drupal technology."[42] Other companies also began to specialize in Drupal development.[43][44]

By 2013, the Drupal website listed hundreds of vendors that offered Drupal-related services.[45]

As of 2014, Drupal is developed by a community.[46][*needs update*] From July 2007 to June 2008, the Drupal.org site provided more than 1.4 million downloads of Drupal software, an increase of approximately 125% from the previous year.[47][48]

As of January 2017 more than 1,180,000 sites use Drupal.[49] These include hundreds of well-known organizations,[50] including corporations, media and publishing companies, governments, non-profits,[51] schools,[52] and individuals. Drupal has won several Packt Open Source CMS Awards[53] and won the Webware 100 [*clarification needed*] three times in a row.[54][55]

Drupal 6 was released on 13 February 2008,[56] on 5 March 2009, Buytaert announced a code freeze for Drupal 7 for 1 September 2009.[57] Drupal 7 was released on 5 January 2011, with release parties in several countries.[58] After that, maintenance on Drupal 5 stopped, with only Drupal 7 and Drupal 6 maintained.[59] Drupal 7's end-of-life was scheduled for November 2021, but given the impact of COVID-19, and the continuing wide usage, the end of life was pushed back until 1 November 2023.[60] This was extended once more as of June 2023 and was finally set for 5 January 2025.[61]

Drupal 8 was first released on 19 November 2015. This was the first to use Symfony for components and Twig) as a template engine and it also used the Composer) for managing dependencies.[62][63] The last Drupal 8 was version 8.9.20 which was released on 17 December 2021.[29]

Drupal 9 was released in 2020 and was created with easier upgrades and management in mind. The first version was released on 3 June 2020 along with Drupal 8.9.0 with fewer major changes in project structure than in version 8.0, but with some of the old, deprecated code removed.[62][64][65]

In October 2022, Drupal released an open source headless CMS accelerator, allowing the front end to be managed outside of the core system.[66][67]

In April 2023, Drupal was recognized by the United Nations Digital Public Good Alliance as a digital public good.[68]

## Drupal Core

In the Drupal community, "core" refers to the collaboratively built codebase that can be extended through contributory modules and—for versions prior to Drupal 8—is kept outside of the "sites" folder of a Drupal installation.[69] (Starting with version 8, the core is kept in its own 'core' sub-directory.) Drupal core is the stock element of Drupal. Common Drupal-specific libraries, as well as the bootstrap process, are defined as Drupal core; all other functionality is defined as Drupal modules including the system module itself.

In a Drupal website's default configuration, authors can contribute content as either registered or anonymous users (at the discretion of the administrator). This content is accessible to web visitors through a variety of selectable criteria. As of Drupal 8, Drupal has adopted some Symfony libraries into Drupal core.

Core modules also include a hierarchical taxonomy) system, which lets developers categorize content or tag) with keywords for easier access.[21]

### Core modules

Drupal core includes modules that can be enabled by the administrator to extend the functionality of the core website.[70][71]

The core Drupal distribution provides a number of features, including:[70]

* Access statistics and logging
* Advanced search
* Books, comments, and forums
* Caching, lazy-loading content (using BigPipe) and feature throttling for improved performance
* Custom content type and fields, and user interface to create, manage, and display lists of content.
* Descriptive URLs
* Multi-level menu system
* Multi-site support[72]
* Multi-user content creation and editing
* RSS feed and feed aggregator
* Security and new release update notification
* User profiles
* Various access control restrictions (user roles, IP addresses, email)
* Workflow tools (triggers and actions)

### Core themes

Drupal includes core themes, which customize the "look and feel" of Drupal sites,[73] for example, Garland and Bartik.

The Color Module, introduced in Drupal core 5.0, allows administrators to change the color scheme of certain themes via a browser interface.[74]

### Drupal CMS

At DrupalCon Portland in 2024, Dries Buytaert called for the Drupal Community to create a new, modernized Drupal experience. The project was initially called Starshot[75] and it was an effort to reframe how people think of Drupal. The project aims to deliver a more user-friendly and out-of-the-box version of Drupal, with a focus on ease of use, faster onboarding, and a polished default experience. In 2025, this project was launched as Drupal CMS. This represents a shift toward making Drupal more accessible to non-developers while retaining its powerful, flexible core architecture.[76][77]

Drupal CMS includes a number of new artificial intelligence features.[78] It also provides tools intended to support open-source, low-code and no-code development approaches.[79]

### Localization

As of September 2022, Drupal is available in 100 languages including English (the default).[80][81] Support is included for right-to-left languages such as Arabic, Persian, and Hebrew.[82]

Drupal localization is built on top of gettext, the GNU internationalization and localization (i18n) library.

### Auto-update notification

Drupal can automatically notify the administrator about new versions of modules, themes, or the Drupal core.[82] It's important to update quickly after security updates are released.

Before updating it is highly recommended to take backup of core, modules, theme, files and database. If there is any error shown after update or if the new update is not compatible with a module, then it can be quickly replaced by a backup. There are several backup modules available in Drupal.

On 15 October 2014, an SQL injection vulnerability was announced and update was released.[83] Two weeks later the Drupal security team released an advisory explaining that everyone should act under the assumption that any site not updated within 7 hours of the announcement was compromised by automated attacks.[84] Thus, it can be extremely important to apply these updates quickly and usage of a tool like drush to make this process easier is highly recommended.

### Database abstraction

Prior to version 7, Drupal had functions that performed tasks related to databases, such as SQL query cleansing, multi-site table name prefixing, and generating proper SQL queries. In particular, Drupal 6 introduced an abstraction layer that allowed programmers to create SQL queries without writing SQL.

Drupal 9 extends the data abstraction layer so that a programmer no longer needs to write SQL queries as text strings. It uses PHP Data Objects to abstract the database. Microsoft has written a database driver for their SQL Server. Drupal 7 supports the file-based SQLite database engine, which is part of the standard PHP distribution.

### Windows development

With Drupal 9's new database abstraction layer, and ability to run on the Windows web server IIS, it is now easier for Windows developers to participate in the Drupal community.

A group on Drupal.org is dedicated to Windows issues.[85]

### Accessibility

Since the release of Drupal 7, Web accessibility has been constantly improving in the Drupal community.[86] Drupal is a framework dedicated for building sites accessible to people with disabilities because many of the best practices have been incorporated into Drupal Core.

Drupal 8 saw many improvements from the Authoring Tool Accessibility Guidelines (ATAG) 2.0 guidelines which support both an accessible authoring environment as well as support for authors to produce more accessible content.

The accessibility team is carrying on the work of identifying and resolving accessibility barriers and raising awareness within the community.

Drupal 8 has good semantic support for rich web applications through WAI-ARIA. There have been many improvements to both the visitor and administrator sides of Drupal, especially:

* Drag-and-drop functionality
* Improved color contrast and intensity
* Adding skip navigation to core themes
* Adding labels by default for input forms
* Fixing CSS display:none with consistent methods for hiding and exposing text on focus
* Adding support for ARIA Live Regions with Drupal.announce
* Adding a TabbingManager to improve keyboard navigation[87]

The community also added an accessibility gate for core issues in Drupal 8.[88]

## Extending the core

Drupal core is modular, defining a system of hooks and callbacks), which are accessed internally via an API.[89] This design allows third-party contributed [modules](#modules) and [themes](#themes) to extend or override Drupal's default behaviors without changing Drupal core's code.

Drupal isolates core files from contributed modules and themes. This increases flexibility and security and allows administrators to cleanly upgrade to new releases without overwriting their site's customizations.[90] The Drupal community has the saying, "Never hack core," a strong recommendation that site developers not change core files.[69]

### Modules

Contributed modules offer such additional or alternate features as image galleries, custom content types and content listings, WYSIWYG editors, private messaging, third-party integration tools,[91] integrating with BPM portals,[92] and more. As of December 2019 the Drupal website lists more than 44,000 free modules.[15]

Some of the most commonly used contributed modules include:[93]

* **Content Construction Kit (CCK):** Allows site administrators to dynamically create content types by extending the database schema. "Content type" describes the kind of information. Content types include, but are not limited to, events, invitations, reviews, articles, and products. The CCK Fields API is in Drupal core in Drupal 7.[94][95]
* **Views:** Facilitates the retrieval and presentation, through a database abstraction system, of content to site visitors. Basic views functionality has been added to core of Drupal 8.[96]
* **Panels:** Drag-and-drop layout manager that allows site administrators to visually design their site.
* **Rules:** Conditionally executed actions based on recurring events.
* **Features:** Enables the capture and management of features (entities, views, fields, configuration, etc.) into custom modules.
* **Context:** Allows the definition of sections of site where Drupal features can be conditionally activated
* **Media:** Makes photo uploading and media management easier
* **Services:** Provides an API for Drupal.

### Themes

As of December 2019, there are more than 2,800 free community-contributed themes).[16] Themes adapt or replace a Drupal site's default look and feel.

Drupal themes use standardized formats that may be generated by common third-party theme design engines. Many are written in the PHPTemplate engine[97] or, to a lesser extent, the XTemplate engine.[98] Some templates use hard-coded PHP. Drupal 8 and future versions of Drupal integrate the Twig) templating engine.[99]

The inclusion of the PHPTemplate and XTemplate engines in Drupal addressed user concerns about flexibility and complexity.[100] The Drupal theming system utilizes a template engine) to further separate HTML/CSS from PHP. A popular Drupal contributed module called 'Devel' provides GUI information to developers and themers about the page build.

Community-contributed themes on the Drupal website are released under a free GPL license.[101][102]

### Distributions

In the past, those wanting a fully customized installation of Drupal had to download a pre-tailored version separately from the official Drupal core. Today, however, a distribution defines a packaged version of Drupal that upon installation, provides a website or application built for a specific purpose.

The distributions offer the benefit of a new Drupal site without having to manually seek out and install third-party contributed modules or adjust configuration settings.[103] They are collections of modules, themes, and associated configuration settings that prepare Drupal for custom operation. For example, a distribution could configure Drupal as a "brochure" site rather than a news site or online store.

## Architecture

Drupal is based on the Presentation Abstraction Control architecture, or PAC.

The menu system acts as the Controller. It accepts input via a single source (HTTP GET and POST)), routes requests to the appropriate helper functions, pulls data out of the Abstraction (nodes and, from Drupal 5 onwards, forms), and then pushes it through a filter to get a Presentation of it (the theme system).

It even has multiple, parallel PAC agents in the form of blocks that push data out to a common canvas (page.tpl.php).[104]

## Community

Drupal.org has a large community of users and developers who provide active community support by coming up with new updates to help improve the functionality of Drupal.[105] As of January 2017 more than 105,400 users are actively contributing.[14] The semiannual DrupalCon conference alternates between North America, Europe and Asia.[106] Attendance at DrupalCon grew from 500 at Szeged in August 2008, to over 3,700 people at Austin, Texas, in June 2014.

Smaller events, known as "Drupal Camps" or DrupalCamp, occur throughout the year all over the world.[107] The annual Florida DrupalCamp brings users together for Coding for a Cause that benefits a local nonprofit organization, as does the annual GLADCamp (Greater Los Angeles Drupal Camp) event, Coders with a Cause.

The Drupal community also organizes professional and semi-professional gatherings called meetups at numerous venues around the world.

There are over 30 national communities[108] around drupal.org offering language-specific support.

By January 2023, The Drop Times became a Drupal-focused media outlet, highlighting stories of relevance to the Drupal community.[109]

### Notable users

Notable users of Drupal include:

* AMD[110]
* Columbia University[111]
* European Commission
* Johnson &amp; Johnson[111]
* McGill University
* NASA[112]
* NBC[113]
* Nokia
* Olympic Games[114]
* Oxford
* Patch
* Pfizer[111]
* Princeton University[111]
* Qualcomm[110]
* Rainforest Alliance[115]
* Smithsonian Institution[111]
* Taboola
* TSMC
* UNICEF[114]
* Universal Music Group[111]
* VISA
* We the People)[116]

## Security

Drupal's policy is to announce the nature of each security vulnerability once the fix is released.[117][118]

Administrators of Drupal sites can be automatically notified of these new releases via the Update Status module (Drupal 6) or via the Update Manager (Drupal 7).[119]

Drupal maintains a security announcement mailing list, a history of all security advisories, a security team home page, and an RSS feed with the most recent security advisories.[120][121][122]

In mid-October 2014, Drupal issued a "highly critical" security advisory regarding an SQL injection bug in Drupal 7, also known as Drupalgeddon.[123][124][125] Downloading and installing an upgrade to Drupal 7.32 fixes the vulnerability, but does not remove any backdoor) installed by hackers if the site has already been compromised).[126] Attacks began soon after the vulnerability was announced. According to the Drupal security team, where a site was not patched within hours of the announcement, it should be considered compromised and taken offline by being replaced with a static HTML page while the administrator of its server must be told that other sites on the same server may also have been compromised. To solve the problem, the site must be restored using backups from before 15 October, be patched and manually updated, and anything merged from the site must be audited.[127]

In late March 2018, a patch for vulnerability CVE-2018-7600, also dubbed *Drupalgeddon2*, was released. The underlying bug allows remote attackers without special roles or permissions to take complete control of Drupal 6, 7, and 8 sites.[128][129] Drupal 6 reached end-of-life on 24 February 2016, and does not get official security updates (extended support is available from two paid Long Term Services Vendors).[130] Starting early April, large scale automated attacks against vulnerable sites were observed, and on 20 April, a high level of penetration of unpatched sites was reported.[131]

On 23 December 2019, Drupal patched an arbitrary file upload flaw. The file-upload flaw affects Drupal 8.8.x before 8.8.1 and 8.7.x before 8.7.11, and the vulnerability is listed as moderately critical by Drupal.[132][133]

In September 2022, Drupal announced two security advisories for a severe vulnerability in Twig for users of Drupal 9.3 and 9.4.[134] That week, Drupal also announced a patch for the S3 File System to fix an access bypass issue.[99]

In January 2023, Drupal announced software updates to resolve four vulnerabilities in Drupal core and three plugins.[135]

## See also

*Free and open-source software portal*

* ▌▌▌▌ Drupal 2013 fork
* List of content management systems

## References

1. ↑ "CHANGELOG.txt". *Drupal.org*. Retrieved 8 June 2020.
2. 1 2 "drupal 11.4.1". Retrieved 8 July 2026.
3. 1 2 "Licensing FAQ". *drupal.org*. Retrieved 8 April 2009.
4. ↑ A query on **Drupal**'s official website in March 2009: How does one pronounce "Drupal"? (accessed 19 June 2013)
5. ↑ "The Drupal Overview". *drupal.org*. 2 June 2008. Retrieved 8 April 2009.
6. ↑ "System Requirements". *drupal.org*. Retrieved 8 April 2009.
7. ↑ "Open Source Usage Distribution in the Top 10k Sites". *BuiltWith Pty Ltd*. 2 January 2022. Retrieved 7 January 2022. `{{cite web}}`: CS1 maint: deprecated archival service (link)
8. ↑ W3Techs (13 June 2022). "Usage Statistics and Market Share of Content Management Systems". *W3Techs*. Retrieved 13 June 2022. `{{cite web}}`: CS1 maint: numeric names: authors list (link)
9. ↑ "The State of Drupal 2010 speech". 10 March 2001. Retrieved 31 August 2011.
10. ↑ "Knowledge management with Drupal". 19 May 2004.
11. ↑ "Drupal launches newest version of the CMS already powering top organizations around the world". *Drupal.org*. Drupal Association. 3 June 2020. Retrieved 10 March 2021. `{{cite web}}`: CS1 maint: deprecated archival service (link)
12. ↑ "Getting Involved | Drupal.org". *www.drupal.org*. 21 December 2019. Retrieved 21 September 2018. Drupal.org Activity `{{cite web}}`: CS1 maint: deprecated archival service (link)
13. ↑ "1 Million Users on Drupal.org!". *www.drupal.org*. 11 October 2013.
14. 1 2 "Drupal for Developers | Drupal.org". *www.drupal.org*. 18 March 2022. Retrieved 21 April 2017. `{{cite web}}`: CS1 maint: deprecated archival service (link)
15. 1 2 "Module Project". *Drupal.org*. 18 March 2022. Archived from the original on 23 June 2023. Retrieved 23 June 2023.
16. 1 2 "Theme project | Drupal.org". *www.drupal.org*. 18 March 2022. Retrieved 21 September 2017. `{{cite web}}`: CS1 maint: deprecated archival service (link)
17. ↑ "Distribution project | Drupal.org". *www.drupal.org*. 18 March 2022. Retrieved 21 September 2017. `{{cite web}}`: CS1 maint: deprecated archival service (link)
18. ↑ "Drupal 7 as an enterprise web application framework". *drupal.org*.
19. ↑ O'Connor, William (19 August 2014). "The Drupal API turns a CMS into a true enterprise application - O'Reilly Radar". *O'Reilly Media*. Retrieved 11 April 2017.
20. ↑ Diana, Dupuis (15 May 2013). "Drupal Is a Framework: Why Everyone Needs to Understand This". *Linux Journal*.
21. 1 2 "Features". *drupal.org*. Retrieved 8 April 2009.
22. ↑ "Drupal". *Sovereign Tech Agency*. Retrieved 13 August 2025. `{{cite web}}`: CS1 maint: deprecated archival service (link)
23. ↑ Reporter, Staff (14 January 2024). "Drupal Association Receives $300,000 Sovereign Tech Fund Contract". *www.thedroptimes.com*. Retrieved 14 August 2025. `{{cite web}}`: CS1 maint: deprecated archival service (link)
24. ↑ Alias, Thomas K (14 April 2023). "Drupal Is Now a Digital Public Good". *The Drop Times*. Retrieved 18 August 2025.
25. ↑ "20 Years of Drupal History". Retrieved 4 September 2024.
26. ↑ https://www.drupal.org/project/drupal/releases/10.6.2. Retrieved 17 January 2026. `{{cite web}}`: Missing or empty `|title=` (help)
27. ↑ "Drupal 7 releases; drupal.org". Retrieved 7 January 2025.
28. ↑ "Drupal 9 releases; drupal.org". Retrieved 21 September 2023.
29. 1 2 "Drupal 8 releases; drupal.org". Retrieved 18 December 2022.
30. ↑ "Drupal 6 releases; drupal.org". Retrieved 1 July 2022.
31. ↑ "Drupal 5 releases; drupal.org". Retrieved 1 July 2022.
32. ↑ "Drupal 4 releases; drupal.org". Retrieved 1 July 2022.
33. ↑ "Files 3.0.0 project / drupal; drupal.org". Retrieved 4 September 2024.
34. ↑ "Files 2.0 project / drupal; drupal.org". Retrieved 4 September 2024.
35. ↑ "Files 1.0 project / drupal; drupal.org". Retrieved 4 September 2024.
36. 1 2 Miller, Ron (22 January 2021). "Drupal's journey from dorm-room project to billion-dollar exit". *TechCrunch*. Retrieved 20 September 2022.
37. ↑ Ruthven, Hunter (17 April 2012). "Dorm room to boardroom - Dries Buytaert on growing Drupal". *Growth Business*. Retrieved 20 September 2022.
38. ↑ Buytaert, Dries (24 January 2008). *Profiling techniques for performance analysis and optimization of Java applications* (PhD).
39. ↑ "Druppel: Dutch to English Translation". *Babylon Translation*. Archived from the original on 13 April 2009. Retrieved 8 April 2009.
40. 1 2 "History". *drupal.org*. Retrieved 8 April 2009.
41. ↑ Melançon, Benjamin; et al. (2011). *The Definitive Guide to Drupal 7* (2nd ed.). Apress. p. 823. ISBN) 9781430231356.
42. ↑ Critchley, Spencer (3 May 2006). "Digital Politics: An Interview With CivicSpace Founder Zack Rosen". *O'Reilly Media*. Archived from the original on 17 May 2006. Retrieved 27 May 2012.
43. ↑ Kreiss, Daniel (5 March 2012). "Dean, Romney, and Drupal: Values and Technological Adoption". *Culture Digitally*. Retrieved 27 May 2012.
44. ↑ Samantha M. Shapiro, "The Dean Connection", *The New York Times* 7 December 2003, accessed 27 May 2012.
45. ↑ "Marketplace". *drupal.org*. Retrieved 18 April 2013.
46. ↑ Koenig, Josh. "Growth Graphs". *Groups.Drupal*. Retrieved 8 April 2009.
47. ↑ Buytaert, Dries (2008). "Drupal Download Statistics". Retrieved 8 April 2009.
48. ↑ Buytaert, Dries (2007). "Drupal Download Statistics". Retrieved 8 April 2009.
49. ↑ "Usage statistics for Drupal core".
50. ↑ "Drupal Sites". *Dries Buytaert*. Retrieved 20 July 2010.
51. ↑ "List of Nonprofit, NPO, NGO Websites Using Drupal". *ENGINE Industries*. Archived from the original on 24 December 2009. Retrieved 20 July 2010.
52. ↑ Critic, C. M. S. (27 February 2024). "Empowering Higher Ed: 4 Strategies to Transform your Drupal CMS into an Open Source DXP at Scale". *CMS Critic*. Retrieved 10 April 2025.
53. ↑ "OSS CMS Award Previous Winners". *Packt Publishing*. Archived from the original on 7 July 2009. Retrieved 8 April 2009.
54. ↑ "Drupal is a Webware 100 winner for the third year in a row". *Drupal.org*. 19 May 2009. Retrieved 31 August 2011.
55. ↑ "Cnet.com". *News.cnet.com*. 19 May 2009. Retrieved 31 August 2011. `{{cite web}}`: CS1 maint: deprecated archival service (link)
56. ↑ "Drupal 6.0 released | Drupal.org". 13 February 2008.
57. ↑ "Buytaert.net". *Buytaert.net*. 4 March 2009. Retrieved 31 August 2011.
58. ↑ "Drupal 7 to be released on January 5 (with one ginormous party)". *Buytaert.net*. 21 December 2010. Retrieved 31 August 2011.
59. ↑ "Xplain Hosting Drupal 7 Quickstart training seminar". Scoop). 16 December 2010.
60. ↑ "Drupal 7's End-of-Life extended to November 1, 2023 - PSA-2022-02-23". 23 February 2022. Retrieved 29 March 2022.
61. ↑ "End of life announcement and changes to Drupal 7 support - PSA-2023-06-07". *Drupal.org*. 7 June 2023. Retrieved 10 January 2024.
62. 1 2 Shetty, Shefali. "How Drupal 8 aims to be future-proof | Opensource.com". *opensource.com*. Retrieved 18 September 2025.
63. ↑ "Drupal 8.0.0 released". *Drupal*. 19 November 2015. Retrieved 18 September 2025.
64. ↑ "Drupal 9.0.0 released". *Drupal*. 9 June 2020. Retrieved 18 September 2025.
65. ↑ "How Drupal 9 was made and what is included". *Drupal*. 7 May 2019. Retrieved 18 September 2025.
66. ↑ Fluckinger, Don (26 October 2022). "Acquia releases open source headless CMS accelerator". *TechTarget*. Retrieved 10 November 2022.
67. ↑ MacManus, Richard (26 October 2022). "How Drupal Fits Into an Increasingly Headless CMS World". *The New Stack*. Retrieved 10 November 2022.
68. ↑ "Drupal officially achieves recognition as a Digital Public Good". *Drupal.org*. 25 April 2023. Archived from the original on 22 January 2025. Retrieved 16 June 2025.
69. 1 2 "Never hack core". *drupal.org*. 16 May 2007.
70. 1 2 "Documentation: Core modules and themes". *drupal.org*. 4 November 2016. Retrieved 22 January 2021.
71. ↑ "Documentation: Core Modules and eCommerce". *lnwebworks.com*. 12 August 2022.
72. ↑ "Documentation: Multisite Drupal". 17 August 2016.
73. ↑ Buytaert, Dries (30 October 2006). "Garland, the new default core theme". *drupal.org*. Retrieved 8 April 2009.
74. ↑ "Color: Allows the user to change the color scheme of certain themes". *drupal.org*. 8 October 2007. Retrieved 8 April 2009.
75. ↑ Quinlan, Keely (10 May 2024). "Drupal announces 'Starshot' release for less-technical users". *StateScoop*. Retrieved 10 April 2025.
76. ↑ Dees, Mels (17 January 2025). "Drupal launches no-code CMS". *Techzine Global*. Retrieved 10 April 2025.
77. ↑ Jacob, Sebin Abraham (21 August 2024). "Why Is It 'Drupal CMS' and Not 'Drupal': An Explainer". *The Drop Times*. Retrieved 18 August 2025.
78. ↑ "Drupal: Power, Flexibility, Freedom, and Now Smarter with AI". *www.thedroptimes.com*. 31 January 2025. Retrieved 10 April 2025.
79. ↑ "Recipes, Starshot, and the future of Drupal | TheDropTimes". *www.thedroptimes.com*. 27 June 2024. Retrieved 10 April 2025.
80. ↑ "Drupal core translation downloads". *drupal.org*. Retrieved 30 January 2017.
81. ↑ Nick, Edward (7 September 2022). "Drupal". *Data Science Central*. Retrieved 20 September 2022.
82. 1 2 "Drupal 6.0 released". *drupal.org*. 13 February 2008. Retrieved 8 April 2009.
83. ↑ "SA-CORE-2014-005 - Drupal core - SQL injection". *Https*. 15 October 2014. Retrieved 3 December 2014.
84. ↑ "Drupal Core - Highly Critical - Public Service announcement - PSA-2014-003". *Https*. 29 October 2014. Retrieved 3 December 2014.
85. ↑ "Drupal on Windows Group". *drupal.org*. Retrieved 14 February 2011.
86. ↑ Killesreiter, Gerhard (25 February 2013). "Accessibility statement". *Drupal.org*. Retrieved 16 April 2013.
87. ↑ "Drupal 8 Accessibility Features". 27 May 2013.
88. ↑ Scholten, Roy (10 December 2012). "Drupal core gates". *Drupal.org*. Retrieved 16 April 2013.
89. ↑ "API Reference". *drupal.org*. Retrieved 8 April 2009.
90. ↑ "File and directory management". *drupal.org*. 7 May 2005.
91. ↑ "Integrating Drupal with External Systems". *specbee.com*. 24 August 2018. Retrieved 24 August 2018.
92. ↑ "Drupal Camunda BPM Integration". *Srijan Technologies*.
93. ↑ "Project usage overview". *Drupal.org*. Retrieved 18 August 2011.
94. ↑ "DRUPAL 5 TO DRUPAL 7". Archived from the original on 4 July 2017. Retrieved 24 March 2015.
95. ↑ "Field API". 2009. Retrieved 8 May 2009.
96. ↑ "Views in Drupal Core initiative: Status report and roadmap". 3 September 2012. Retrieved 4 November 2014.
97. ↑ "PHPTemplate theme engine". *drupal.org*. Archived from the original on 8 March 2009. Retrieved 8 April 2009.
98. ↑ "XTemplate theme engine". *drupal.org*. Archived from the original on 16 March 2009. Retrieved 8 April 2009.
99. 1 2 Arghire, Ionut (29 September 2022). "Drupal Updates Patch Vulnerability in Twig Template Engine | SecurityWeek.Com". *www.securityweek.com*. Retrieved 11 October 2022.
100. ↑ "How does Drupal compare to ▌▌▌▌? discussion thread". *drupal.org*. 17 January 2005. Retrieved 8 April 2009.
101. ↑ "Drupal themes". *Drupal.org*. Archived from the original on 23 August 2007. Retrieved 31 August 2011.
102. ↑ "Adding your theme to Drupal.org". *Drupal.org*.
103. ↑ "Top Drupal Distributions". AGLOBALWAY Consulting Services. Archived from the original on 13 April 2014.
104. ↑ "MVC vs. PAC".
105. ↑ Drupal - CMS Grew Overnight By MAAN Softwares, Retrieved, 8 June 2017
106. ↑ "drupal.org discussion on DrupalCon event management". *Groups.drupal.org*. Retrieved 31 August 2011.
107. ↑ "Drupal Camps and Cons". Retrieved 25 January 2013.
108. ↑ "Language specific communities". *Drupal.org*. 26 August 2011. Retrieved 31 August 2011.
109. ↑ "The Drop Times". *Talking Drupal* (Podcast). No. 384. Retrieved 5 November 2025.
110. 1 2 Kaur Dadiala, Karanjeet (8 August 2022). "16 Organization Websites Built Using Drupal in 2022". *Zyxware Technologies*. Retrieved 9 October 2022.
111. 1 2 3 4 5 6 Montti, Roger (22 April 2022). "Drupal Warns of Two Critical Vulnerabilities". *Search Engine Journal*. Retrieved 23 October 2022.
112. ↑ Caron, Bruce (20 May 2015). "NASA Science on Drupal Central". *EarthData.NASA.gov*. Retrieved 5 October 2022.
113. ↑ Fluckinger, Don (10 March 2021). "Acquia digital experience platform adds CX-friendly tools". *TechTarget*. Retrieved 6 December 2022.
114. 1 2 Blyaert, Luc (18 October 2022). "Tobania trekt CM binnen met Dries Buytaert". *www.computable.be* (in Dutch). Retrieved 18 October 2022.
115. ↑ "Who Uses Drupal? 10 Famous Drupal Websites". *Smartbees.co*. 27 May 2021. Retrieved 5 October 2022.
116. ↑ Spencer, Jamie (7 April 2017). "CMS Battle for Beginners: ▌▌▌▌ vs ▌▌▌▌ vs Drupal (Infographic)". *MakeAWebsiteHub.com*. Retrieved 17 May 2017.
117. ↑ Drupal (October 2005). "Security announcement and release process".
118. ↑ "How to report a security issue". Drupal.
119. ↑ "Update manager (and Update status)". *drupal.org*. Retrieved 1 July 2011.
120. ↑ "Security advisories". *drupal.org*. Retrieved 28 April 2009.
121. ↑ "Drupal security team". *Drupal.org*. October 2005. Retrieved 31 August 2011.
122. ↑ "Drupal Security RSS feed". *Drupal.org*. Retrieved 31 August 2011.
123. ↑ Leyden, John (3 November 2014). "Drupal megaflaw raises questions over CMS bods' crisis mgmt". *www.theregister.com*.
124. ↑ "SA-CORE-2014-005 - Drupal core - SQL injection". *Security advisories*. Drupal security team. 15 October 2014.
125. ↑ "Drupalgeddon strikes back: outdated Drupal allegedly linked to "Panama Papers"". *Blog*. Drop Guard. Archived from the original on 11 June 2016. Retrieved 13 July 2016.
126. ↑ "Drupal Core—Highly Critical—Public Service Announcement—PSA-2014-003". *Security advisories*. Drupal security team. 29 October 2014 – via Drupal.org. "You should proceed under the assumption that every Drupal 7 website was compromised unless updated or patched before Oct 15th, 11pm UTC, that is 7 hours after the announcement. **Simply updating to Drupal 7.32 will not remove backdoors**....updating to version 7.32 or applying the patch fixes the vulnerability but does not fix an already compromised website. If you find that your site is already patched but you didn't do it, that can be a symptom that the site was compromised—some attacks have applied the patch as a way to guarantee they are the only attacker in control of the site."
127. ↑ Robinson, Brian (7 November 2014). "Attacks on open source call for better software design -". *GCN*. Archived from the original on 18 August 2016. Retrieved 29 July 2016.
128. ↑ "How we installed a Drupal security patch on 1300 sites, stress-free!". *Dropsolid*. 4 April 2018. Retrieved 11 March 2019.
129. ↑ "FAQ about SA-CORE-2018-002". Drupal Security Team. Retrieved 23 April 2018.
130. ↑ "Drupal 6 end-of-life announcement". *Drupal.org*. 9 November 2015. Retrieved 1 May 2021.
131. ↑ Goddin, Dan (20 April 2018). "Drupalgeddon2" touches off arms race to mass-exploit powerful Web servers". Ars Technica. Retrieved 23 April 2018.
132. ↑ "Drupal Patches Arbitrary File Upload Flaw". *Decipher*. 23 December 2019. Retrieved 23 December 2019.
133. ↑ "Drupal core - Moderately critical - Denial of Service - SA-CORE-2019-009". *Drupal.org*. 18 December 2019. Retrieved 23 December 2019.
134. ↑ Montti, Roger (1 October 2022). "Drupal Warns of Critical High Severity Vulnerability". *Search Engine Journal*. Retrieved 11 October 2022.
135. ↑ Arghire, Ionut (20 January 2023). "Drupal Patches Vulnerabilities Leading to Information Disclosure". *www.securityweek.com*. Retrieved 20 January 2023.

## Further reading

* Abbott/Jones (2016), Learning Drupal 8, England, Packt Publishing. ISBN) 978-1-78216-875-1
* Pol, Kristen (2012). *Drupal 7 Multilingual Sites*. Birmingham, England: Packt Publishing. ISBN) 978-1-84951-818-5.
* Mercer, David (2010). *Drupal 7*. Birmingham, England: Packt Publishing. ISBN) 978-1-84951-286-2.
* Travis, Brian (2011). *Pro Drupal 7 for Windows Developers*. Berkeley: APress. ISBN) 978-1-4302-3153-0.
* Butcher, Matt; Larry Garfield; John Wilkins; Matt Farina; Ken Rickard; Greg Dunlap (2010). *Drupal 7 Module Development*. Birmingham, England: Packt Publishing. ISBN) 978-1-84951-116-2.
* Bhavin, Patel (August 2010). *Drupal 6 Panel Cookbook*. Canada: Packt Publishing. ISBN) 978-1-84951-118-6.
* Beighley, Lynn (2009). *Drupal for Dummies*. New York: For Dummies. ISBN) 978-0-470-55611-5.
* Herremans, D. (2009). *Drupal 6: Ultimate Community Site Guide*. Switzerland. ISBN) 978-2-8399-0490-2. `{{cite book}}`: CS1 maint: location missing publisher (link)
* Peacock, Michael (2008). *Selling Online with Drupal e-Commerce*. Birmingham, England: Packt Publishing. ISBN) 978-1-84719-406-0.
* VanDyk, John K. (2008). *Pro Drupal Development, Second Edition*. New York: Springer Verlag/Apress. ISBN) 978-1-4302-0989-8.
* Kafer, Konstantin; Emma Hogbin (April 2009). *Front End Drupal: Designing, Theming, Scripting*. Jersey, USA: Prentice Hall. ISBN) 978-0-13-713669-8.

## External links

&gt; Wikimedia Commons has media related to **Drupal**.

* Official website ✎

---

**Application frameworks** (v · t · e)

| | |
|---|---|
| Open-source | Apache Cordova · Avalonia UI) · Codename One · .NET MAUI · Eclipse Rich Client Platform#Rich_client_platform) · Electron) · Flutter) · Griffon) · Ionic) · JavaFX · JUCE · Kivy) · Mozilla application framework · NativeScript · NetBeans Platform · Qt) · React Native · Tauri) · Titanium SDK · Uno Platform · Windows Forms · Windows Presentation Foundation |
| Proprietary | Cocoa) · .NET Framework · FireMonkey · MacApp · Microsoft Foundation Class Library · Oracle Application Development Framework · SwiftUI · UIKit · Visual Component Library |

**Software in the Public Interest** (v · t · e)

| | |
|---|---|
| Projects | 0 A.D.) · Arch Linux · Debian · Drizzle) · Drupal · FFmpeg · Fluxbox · freedesktop.org · FreedomBox · Gallery Project · GNU TeXmacs · GNUstep · Jenkins) · LibreOffice · MinGW · Open and Free Technology Community · Open Bioinformatics Foundation · Open64 · OpenEmbedded · OpenVAS · OpenWrt · OpenZFS · PostgreSQL · Privoxy · SproutCore · X.Org Foundation |
| People | Martin Michlmayr (President) · Bdale Garbee |

**Authority control databases** ✎

| | |
|---|---|
| International | VIAF · GND |
| National | United States · Czech Republic · Israel · Catalonia |

Retrieved from "https://en.wikipedia.org/w/index.php?title=Drupal&amp;oldid=1360936209"

**Categories**: Drupal | 2000 software | Blog software | Cross-platform software | Free content management systems | Free software programmed in PHP | PHP frameworks | Software using the GNU General Public License | Web frameworks | Website management | Web development software

**Hidden categories**: Articles with short description | Short description is different from Wikidata | Use dmy dates from November 2025 | Articles lacking reliable references from October 2022 | All articles lacking reliable references | CS1 maint: deprecated archival service | CS1 maint: numeric names: authors list | Articles containing potentially dated statements from March 2022 | All articles containing potentially dated statements | CS1 errors: missing title | CS1 errors: bare URL | Articles containing potentially dated statements from 2014 | Wikipedia articles in need of updating from October 2022 | All Wikipedia articles in need of updating | Articles containing potentially dated statements from January 2017 | Wikipedia articles needing clarification from October 2017 | Articles containing potentially dated statements from December 2019 | CS1: unfit URL | CS1 Dutch-language sources (nl) | CS1 maint: location missing publisher | Commons category link from Wikidata
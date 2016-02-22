<%-- SS Meta --%>
$MetaTags(false)

<%-- basic meta --%>
<meta name="keywords" content="$Meta('Keywords')">
<meta name="description" content="$Meta('Description')">
<link rel="canonical" href="$Meta('Link')">

<%-- Schema.org markup for Google+ --%>
<meta itemprop="name" content="$Meta('Title')">
<meta itemprop="description" content="$Meta('Description')">
<meta itemprop="image" content="$Meta('Image')">
<meta itemprop="url" content="$Meta('Link')" />

<%-- Twitter Card data --%>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="$Meta('TwitterSite')">
<meta name="twitter:title" content="$Meta('Title')">
<meta name="twitter:description" content="$Meta('Description')">
<meta name="twitter:creator" content="$Meta('TwitterCreator')">
<meta name="twitter:image:src" content="$Meta('Image')">

<%-- Open Graph data --%>
<meta property="og:title" content="$Meta('Title')" />
<meta property="og:type" content="article" />
<meta property="og:url" content="$Meta('Link')" />
<meta property="og:image" content="$Meta('Image')" />
<meta property="og:description" content="$Meta('Description')" />
<meta property="og:site_name" content="$Meta('SiteName')" />
<meta property="article:published_time" content="$Meta('TimeCreated')" />
<meta property="article:modified_time" content="$Meta('TimeModified')" />
<%-- <meta property="article:section" content="Article Section" /> --%>
<%-- <meta property="article:tag" content="Article Tag" /> --%>
<%-- <meta property="fb:admins" content="Facebook numberic ID" /> --%>

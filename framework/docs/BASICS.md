# The Basics

Welcome to the Leanpub Browser book writing editor! This page is where you will be writing your book. This file is a working, real example of how to write a book chapter in plain text with Markdown formatting (which we'll explain briefly below).

This is a paragraph. You just write.

Blank lines are used to separate paragraphs.

To make *italic text* you surround it with *single* asterisks. To make **bold text** you surround it with **double** asterisks.

## Previewing and publishing

You can preview or publish your ebook by clicking a button!

To preview your book:

1. Click Useful Links on the left
2. Click Preview Page
3. Click the Create Preview button

To publish your book:

* Click Useful Links on the left
* Click Publish Page
* Click the Publish Book button

Yes, these instructions are not just help text: they are also a book manuscript which you can preview or even publish!

Once the book has been generated, you can download it in PDF or EPUB format to see what it looks like.

**In fact, we've already created a preview of your brand new book for you, just now!**

Just click on the Useful Links on the left, and click the Preview Page link to see it!

(If you get to the preview page and there are no ebook links yet, congratulations: you're a really fast reader! In that case, just wait a minute and then reload the page to see them.)

*Or, if you don't believe book publishing could possibly be this easy, just edit some of the text on this page and create your own preview from the Preview page by clicking the Create Preview button.*

## Basic formatting

You can start new chapters by starting a line with a # sign and a space, and then typing your chapter title, just like you can see at the top of this page, where you see # The Basics on a line by itself.

You can create multiple chapters in one file, but we recommend one chapter per file. This way, your manuscript files will be easier to navigate. So, for example, this chapter is contained in the file named "The Basics" that you can see listed under the "Manuscript" menu to the left. "The Basics" is in bold in the menu, because it is the file that is currently selected.

(If you start typing in here, the Manuscript menu and all the other menus will go away, so that you can relax and focus on your writing! Just click outside of this text area, and all the menus will come back.)

You can make a thematic break with three asterisks, like this:

* * *

By the way, the above formatting is actually *all* you need to know to write a typical novel using Markdown formatting! To write a technical book, however, you'll need to know a bit more Markdown, and learn a bit about Markua.

## Markdown and Markua

The dialect of Markdown used at Leanpub is called Markua.

Markdown is great, but it doesn't have support for certain things that many books need. These include index entries, crosslinks, endnotes, external code samples, etc. So, we've added these to Markdown, by extending Markdown in an open specification called Markua.

The Markua spec is [here](http://markua.com).

However, that spec is really long, and there are a few advanced things in the spec which aren't fully supported in Leanpub yet.

So, if you want to learn how to get started writing in Markua on Leanpub, see the next chapter, **Writing in Markua**, by clicking the Writing in Markua chapter under the Manuscript menu to the left.

This chapter will show you everything you need to know about how to use all the important parts of Markdown and Markua to create a technical book, including how to use...

   * lists
   * images
   * code samples
   * tables
   * math
   * resource attributes
   * document settings
   * asides and blurbs
   * index entries
   * crosslinks

Seriously, unless you already know everything about Markua, you should strongly consider looking at that chapter. It may save you a bunch of time!

## Generate a preview version of your book

You should probably generate a preview of this version of your book next, to save a copy of these helpful instructions. (The **Writing in Markua** chapter can come in handy!)

Click on the Versions tab above to go to the Preview page, and then click Create Preview to do that.

## Either read a tutorial, or just go for it!

At this point, there are two good ways to proceed. You can either read a tutorial and follow along, or you can just go for it!

(If you're feeling adventurous, just go for it. The tutorial is linked on the Help > Getting Started page, so you can always read it later...)

### Read the tutorial...

If this is your first time writing a Leanpub book in our Browser writing mode, after you've read the Writing in Markua we recommend you read our full tutorial [here](https://bit.ly/3lAaI3I). That tutorial will guide you through every step, including previewing versions for your review, and even publishing the first version of your book when you're ready!


# Writing in Markua

Writing in Markua is easy! You can learn most of what you need to know with just a few examples.

To make *italic text* you surround it with single asterisks. To make **bold text** you surround it with double asterisks.

## Section One

You can start new sections by starting a line with two # signs and a space, and then typing your section title.

### Sub-Section One

You can start new sub-sections by starting a line with three # signs and a space, and then typing your sub-section title.

## Including a Chapter in the Sample Book

At the top of this file, you will also see a line at the top:

```
{sample: true}
```

Leanpub has the ability to make a sample book, which interested readers can download or read online. If you add this line above a chapter heading, then when you publish your book, this chapter will be included in a separate sample book for these interested readers.

## Links

You can add web links easily.

Here's a link to the [Leanpub homepage](https://leanpub.com).

## Images

You can add an image to your book in a similar way.

First, add the image to the "Resources" folder for your book. You will find the "Resources" folder under the "Manuscript" menu to the left. 

If you look in your book's "Resources" folder right now, you will see that there is an example image there with the file name "palm-trees.jpg". Here's how you can add this image to your book:

(see Leanpub documentation)

## Lists

### Numbered Lists

You make a numbered list like this:

1. kale
2. carrot
3. ginger

### Bulleted Lists

You make a bulleted list like this:

* kale
* carrot
* ginger

### Definition Lists

You can even have definition lists!

(see Leanpub documentation)

## Code Samples

You can add code samples really easily. Code can be in separate files (a "local" resource) or in the manuscript itself (an "inline" resource).

### Local Code Samples

Here's a local code resource:

(see Leanpub documentation)

### Inline Code Samples

Inline code samples can either be spans or figures.

A span looks like `puts "hello world"` this.

A figure looks like this:

```ruby
puts "hello"
```

You can also add a figure title using the title attribute:

{title: "Hello World in Ruby"}
```ruby
puts "hello"
```

## Tables

You can insert tables easily inline, using the GitHub Flavored Markdown (GFM) table syntax:

| Header 1  | Header 2 |
| --- | --- |
| Content 1 | Content 2 |
| Content 3 | Content 4 Can be Different Length |

Tables work best for numeric tabular data involving a small number of columns containing small numbers:

| Central Bank | Rate      |
|--------------|-----------|
| JPY          | -0.10%    |
| EUR          |  0.00%    |
| USD          |  0.00%    |
| CAD          |  0.25%    |

Definition lists are preferred to tables for most use cases, since reading a large table with many columns is terrible on phones and since typing text in a table quickly gets annoying.

## Math

## Headings

### Sub-section

This is a paragraph.

#### Sub-sub-section

This is a paragraph.

##### Sub-sub-sub-section

This is a paragraph.

###### Sub-sub-sub-sub-section

This is a paragraph.
```

Note the use of three backticks in the above example, to treat the Markua like
inline code (instead of actually like headers).

The other style of headers, called Setext headers, has the following headings:

```
## Block quotes, Asides and Blurbs


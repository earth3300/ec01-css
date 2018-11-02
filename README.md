# EC01 CSS

A set of CSS files used in this project. These files are broken down into several components to make them easier to use in various projects. The first is called `bootstrap.css`, the second `main.css`, the third `color.css`, the fourth `sprite.css`, the fifth `device.css`, and the sixth optional file is called `child.css`. By breaking down any project into multiple components it is easier to deal with. CSS is no exception. 

Instead of a focus on responsiveness, with smaller view screens getting priority, the css file focusing on responsiveness has been called "device.css". This is because--in most cases--the browser is at full size on the device, and larger monitors should get the recognition they deserve. In addition, device specific styling may be available and become more developed in the future (such as that dealing with landscape or portrait orientation). 

If the styling is set up correctly, each device or monitor should display the page correctly, without interfering with the other. That is the goal.

In addition, a file called `style.all.css` MAY contain all of the files used concatenated into one file. Finally, a file called `style.min.css` SHOULD contain all of the files in `style.all.css`, but compressed, with comments spacing and line returns removed.

## Security

In order to write the files to the storage device, there are five security measures in place:

1. The `css.php` file should be placed on a local machine, not online, out in the wild.
2. A check is done to see if this operation is being carried out on the local server.
2. An empty file, called `.security` needs to be present in the working directory.
3. The parameter `?print` needs to be set.
4. The paramater `&unlock` ALSO needs to be set

If all of these conditions are not met, the file will NOT be printed.

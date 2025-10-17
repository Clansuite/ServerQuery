# Troubleshooting

## Common Issues

### Server Not Responding

**Problem**: Queries return false or no data.

**Solutions**:
- Verify the server IP, port, and protocol are correct.
- Check if the server is online using external tools.
- Ensure your network allows outbound connections to the server port.
- Try a different protocol if unsure.

### Class Not Found Errors

**Problem**: `Class 'Clansuite\ServerQuery\CSQuery' not found`.

**Solutions**:
- Run `composer dump-autoload` to regenerate the autoloader.
- Ensure `vendor/autoload.php` is included in your script.
- Check that the library is installed via Composer.

### PHP Version Issues

**Problem**: Errors about unsupported PHP features.

**Solutions**:
- Upgrade to PHP 8.2 or higher.
- Check `php --version` to confirm your version.

### Web Interface Not Working

**Problem**: Blank page or errors in browser.

**Solutions**:
- Ensure `serializer.php` is in the web root and accessible.
- Check PHP error logs for details.
- Verify URL parameters (protocol, host, port).

### Capture Tool Fails

**Problem**: `bin/capture` exits with error.

**Solutions**:
- Ensure the script is executable: `chmod +x bin/capture`.
- Check permissions for writing to `tests/fixtures/`.
- Verify the target server is reachable.

## FAQ

**Q: Can I query voice servers?**

A: The library focuses on game servers, but some protocols may work for voice servers.
Check the supported list. We have support for Teamspeak3, Mumble and Ventrilo.

**Q: How do I add support for a new game?**

A: See the [Developer Manual](../developer-manual/contribution.md) for extending protocols.

**Q: Are there rate limits?**

A: No built-in limits, but respect server operators and avoid excessive queries.

**Q: Can I use this in production?**

A: Yes, but test thoroughly and handle errors gracefully.

**Q: How do fixtures work?**

A: Captured responses are saved as JSON files for replay in tests,
ensuring consistent results without live servers.

## Getting Help

- Check the [examples/](https://github.com/Clansuite/ServerQuery/tree/master/examples) directory.
- Review existing issues on GitHub.
- For bugs, provide PHP version, protocol, and error details.

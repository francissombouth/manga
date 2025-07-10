import React from 'react';
import { Link as RouterLink, useNavigate } from 'react-router-dom';
import {
  AppBar,
  Box,
  Toolbar,
  Typography,
  Button,
  Container,
  IconButton,
  Menu,
  MenuItem,
  Drawer,
  List,
  ListItem,
  ListItemText,
  ListItemIcon,
  useTheme,
  useMediaQuery,
  SwipeableDrawer,
  Divider,
} from '@mui/material';
import {
  AccountCircle,
  Menu as MenuIcon,
  Home,
  LibraryBooks,
  Collections,
  Login,
  PersonAdd,
  Close as CloseIcon,
} from '@mui/icons-material';
import { useAuth } from '../contexts/AuthContext';

function Layout({ children }) {
  const { user, logout, isAuthenticated } = useAuth();
  const navigate = useNavigate();
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  
  const [anchorEl, setAnchorEl] = React.useState(null);
  const [mobileOpen, setMobileOpen] = React.useState(false);

  const handleMenu = (event) => {
    setAnchorEl(event.currentTarget);
  };

  const handleClose = () => {
    setAnchorEl(null);
  };

  const handleDrawerToggle = () => {
    setMobileOpen(!mobileOpen);
  };

  const handleDrawerClose = () => {
    setMobileOpen(false);
  };

  const handleLogout = () => {
    logout();
    handleClose();
    navigate('/');
    if (mobileOpen) setMobileOpen(false);
  };

  const handleNavigation = (path) => {
    navigate(path);
    handleDrawerClose();
  };

  const drawer = (
    <Box 
      sx={{ 
        width: 280,
        height: '100%',
        display: 'flex',
        flexDirection: 'column',
        bgcolor: 'background.paper',
      }} 
      role="presentation"
    >
      <Box sx={{ 
        display: 'flex', 
        alignItems: 'center', 
        justifyContent: 'space-between',
        p: 2,
        borderBottom: 1,
        borderColor: 'divider'
      }}>
        <Typography variant="h6" component="div">
          Menu
        </Typography>
        <IconButton onClick={handleDrawerClose} sx={{ color: 'text.primary' }}>
          <CloseIcon />
        </IconButton>
      </Box>

      <List sx={{ flex: 1, pt: 0 }}>
        <ListItem button onClick={() => handleNavigation('/')} sx={{ py: 2 }}>
          <ListItemIcon><Home /></ListItemIcon>
          <ListItemText primary="Accueil" />
        </ListItem>
        <ListItem button onClick={() => handleNavigation('/oeuvres')} sx={{ py: 2 }}>
          <ListItemIcon><LibraryBooks /></ListItemIcon>
          <ListItemText primary="Œuvres" />
        </ListItem>
        {isAuthenticated ? (
          <>
            <ListItem button onClick={() => handleNavigation('/collections')} sx={{ py: 2 }}>
              <ListItemIcon><Collections /></ListItemIcon>
              <ListItemText primary="Mes Collections" />
            </ListItem>
            <Divider />
            <ListItem button onClick={handleLogout} sx={{ py: 2 }}>
              <ListItemIcon><AccountCircle /></ListItemIcon>
              <ListItemText primary="Déconnexion" />
            </ListItem>
          </>
        ) : (
          <>
            <Divider />
            <ListItem button onClick={() => handleNavigation('/login')} sx={{ py: 2 }}>
              <ListItemIcon><Login /></ListItemIcon>
              <ListItemText primary="Connexion" />
            </ListItem>
            <ListItem button onClick={() => handleNavigation('/register')} sx={{ py: 2 }}>
              <ListItemIcon><PersonAdd /></ListItemIcon>
              <ListItemText primary="Inscription" />
            </ListItem>
          </>
        )}
      </List>
    </Box>
  );

  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', minHeight: '100vh' }}>
      <AppBar 
        position="fixed" 
        sx={{ 
          width: '100%',
          left: 0,
          bgcolor: 'background.paper',
          borderBottom: 1,
          borderColor: 'divider',
          boxShadow: 1,
        }}
      >
        <Container maxWidth={false}>
          <Toolbar disableGutters sx={{ px: { xs: 2, sm: 3 } }}>
            <IconButton
              color="inherit"
              aria-label="open drawer"
              edge="start"
              onClick={handleDrawerToggle}
              sx={{ 
                mr: 2,
                display: { xs: 'flex', md: 'none' },
                color: 'text.primary'
              }}
            >
              <MenuIcon />
            </IconButton>
            
            <Typography
              variant="h6"
              component={RouterLink}
              to="/"
              sx={{ 
                flexGrow: 1, 
                textDecoration: 'none', 
                color: 'text.primary',
                fontSize: { xs: '1.1rem', sm: '1.3rem', md: '1.5rem' },
                fontWeight: 600
              }}
            >
              Bibliothèque
            </Typography>

            <Box sx={{ display: { xs: 'none', md: 'flex' }, alignItems: 'center' }}>
              <Button
                color="inherit"
                component={RouterLink}
                to="/oeuvres"
                sx={{ 
                  mx: 1,
                  color: 'text.primary',
                  '&:hover': {
                    bgcolor: 'action.hover'
                  }
                }}
              >
                Œuvres
              </Button>
              
              {isAuthenticated ? (
                <>
                  <Button
                    color="inherit"
                    component={RouterLink}
                    to="/collections"
                    sx={{ 
                      mx: 1,
                      color: 'text.primary',
                      '&:hover': {
                        bgcolor: 'action.hover'
                      }
                    }}
                  >
                    Mes Collections
                  </Button>
                  <IconButton
                    size="large"
                    aria-label="compte utilisateur"
                    aria-controls="menu-appbar"
                    aria-haspopup="true"
                    onClick={handleMenu}
                    sx={{ 
                      ml: 1,
                      color: 'text.primary'
                    }}
                  >
                    <AccountCircle />
                  </IconButton>
                  <Menu
                    id="menu-appbar"
                    anchorEl={anchorEl}
                    anchorOrigin={{
                      vertical: 'bottom',
                      horizontal: 'right',
                    }}
                    keepMounted
                    transformOrigin={{
                      vertical: 'top',
                      horizontal: 'right',
                    }}
                    open={Boolean(anchorEl)}
                    onClose={handleClose}
                    PaperProps={{
                      sx: {
                        mt: 1,
                        boxShadow: 2,
                        borderRadius: 1
                      }
                    }}
                  >
                    <MenuItem onClick={handleLogout}>Déconnexion</MenuItem>
                  </Menu>
                </>
              ) : (
                <>
                  <Button
                    color="inherit"
                    component={RouterLink}
                    to="/login"
                    sx={{ 
                      mx: 1,
                      color: 'text.primary',
                      '&:hover': {
                        bgcolor: 'action.hover'
                      }
                    }}
                  >
                    Connexion
                  </Button>
                  <Button
                    variant="contained"
                    component={RouterLink}
                    to="/register"
                    sx={{ 
                      ml: 1,
                      bgcolor: 'primary.main',
                      color: 'primary.contrastText',
                      '&:hover': {
                        bgcolor: 'primary.dark'
                      }
                    }}
                  >
                    Inscription
                  </Button>
                </>
              )}
            </Box>
          </Toolbar>
        </Container>
      </AppBar>

      <SwipeableDrawer
        variant="temporary"
        anchor="left"
        open={mobileOpen}
        onOpen={handleDrawerToggle}
        onClose={handleDrawerClose}
        ModalProps={{
          keepMounted: true, // Better open performance on mobile.
        }}
        sx={{
          display: { xs: 'block', md: 'none' },
          '& .MuiDrawer-paper': { 
            boxSizing: 'border-box',
            width: 280,
            bgcolor: 'background.paper',
          },
        }}
      >
        {drawer}
      </SwipeableDrawer>

      <Box 
        component="main" 
        sx={{ 
          flexGrow: 1,
          pt: { xs: 8, sm: 9 }, // Add padding to account for fixed AppBar
          pb: { xs: 2, sm: 3, md: 4 },
        }}
      >
        <Container maxWidth="lg">
          {children}
        </Container>
      </Box>

      <Box
        component="footer"
        sx={{
          py: { xs: 2, sm: 2.5, md: 3 },
          px: 2,
          mt: 'auto',
          bgcolor: 'background.paper',
          borderTop: 1,
          borderColor: 'divider',
        }}
      >
        <Container maxWidth="lg">
          <Typography 
            variant="body2" 
            color="text.secondary" 
            align="center"
            sx={{ fontSize: { xs: '0.8rem', sm: '0.9rem', md: '1rem' } }}
          >
            © {new Date().getFullYear()} Bibliothèque. Tous droits réservés.
          </Typography>
        </Container>
      </Box>
    </Box>
  );
}

export default Layout; 
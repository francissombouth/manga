import React from 'react';
import { Link as RouterLink } from 'react-router-dom';
import {
  Box,
  Typography,
  Button,
  Container,
  Grid,
  Paper,
} from '@mui/material';
import { useAuth } from '../contexts/AuthContext';

function Home() {
  const { isAuthenticated } = useAuth();

  return (
    <Container maxWidth="lg">
      <Box sx={{ mt: 8, mb: 4 }}>
        <Typography
          component="h1"
          variant="h2"
          align="center"
          color="text.primary"
          gutterBottom
        >
          Bienvenue dans votre Bibliothèque
        </Typography>
        <Typography variant="h5" align="center" color="text.secondary" paragraph>
          Découvrez, organisez et partagez vos œuvres littéraires préférées.
          Créez des collections personnalisées et suivez votre progression de lecture.
        </Typography>
        <Box sx={{ mt: 4, display: 'flex', justifyContent: 'center' }}>
          {!isAuthenticated ? (
            <Button
              variant="contained"
              color="primary"
              size="large"
              component={RouterLink}
              to="/register"
            >
              Commencer
            </Button>
          ) : (
            <Button
              variant="contained"
              color="primary"
              size="large"
              component={RouterLink}
              to="/oeuvres"
            >
              Explorer les œuvres
            </Button>
          )}
        </Box>
      </Box>

      <Grid container spacing={4}>
        <Grid item xs={12} md={4}>
          <Paper
            sx={{
              p: 3,
              display: 'flex',
              flexDirection: 'column',
              height: 240,
            }}
          >
            <Typography component="h2" variant="h5" gutterBottom>
              Découvrez
            </Typography>
            <Typography>
              Explorez une vaste collection d'œuvres littéraires, classées par
              genres, auteurs et collections.
            </Typography>
          </Paper>
        </Grid>
        <Grid item xs={12} md={4}>
          <Paper
            sx={{
              p: 3,
              display: 'flex',
              flexDirection: 'column',
              height: 240,
            }}
          >
            <Typography component="h2" variant="h5" gutterBottom>
              Organisez
            </Typography>
            <Typography>
              Créez vos propres collections et organisez vos lectures selon vos
              préférences.
            </Typography>
          </Paper>
        </Grid>
        <Grid item xs={12} md={4}>
          <Paper
            sx={{
              p: 3,
              display: 'flex',
              flexDirection: 'column',
              height: 240,
            }}
          >
            <Typography component="h2" variant="h5" gutterBottom>
              Suivez
            </Typography>
            <Typography>
              Gardez une trace de votre progression de lecture et découvrez de
              nouvelles œuvres recommandées.
            </Typography>
          </Paper>
        </Grid>
      </Grid>
    </Container>
  );
}

export default Home; 